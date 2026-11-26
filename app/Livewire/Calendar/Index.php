<?php

namespace App\Livewire\Calendar;

use App\Models\CalendarEventSnapshot;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public string $syncStartAt = '';

    public string $syncEndAt = '';

    public function mount(): void
    {
        $timezone = config( 'app.timezone' );
        $now = CarbonImmutable::now( $timezone );

        $this->syncStartAt = $now->startOfYear()->format( 'Y-m-d\\TH:i:s' );
        $this->syncEndAt = $now->format( 'Y-m-d\\TH:i:s' );
    }

    public function syncCalendars( GoogleCalendarService $calendar ): void
    {
        $validated = $this->validate(
            [
            'syncStartAt' => ['required', 'date_format:Y-m-d\\TH:i:s'],
            'syncEndAt' => ['required', 'date_format:Y-m-d\\TH:i:s', 'after:syncStartAt'],
            ],
            [
            'syncStartAt.required' => __( 'The sync start date is required.' ),
            'syncStartAt.date_format' => __( 'Enter a valid sync start date.' ),
            'syncEndAt.required' => __( 'The sync end date is required.' ),
            'syncEndAt.date_format' => __( 'Enter a valid sync end date.' ),
            'syncEndAt.after' => __( 'The sync end date must be later than the start date.' ),
            ]
        );

        $timezone = config( 'app.timezone' );
        $start = CarbonImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $validated['syncStartAt'], $timezone );
        $end = CarbonImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $validated['syncEndAt'], $timezone );
        $calendarId = (string) config( 'services.google.calendar_id', 'primary' );

        try {
            $events = $calendar->events( $start->toIso8601String(), $end->toIso8601String(), $calendarId );
            $now = now();
            $snapshots = [];

            foreach ($events as $event) {
                if (($event['status'] ?? null) === 'cancelled' || empty( $event['id'] )) {
                    continue;
                }

                $eventStart = $event['start'] ?? [];
                $eventEnd = $event['end'] ?? [];
                $startsAt = $eventStart['dateTime'] ?? ($eventStart['date'] ?? null);
                $endsAt = $eventEnd['dateTime'] ?? ($eventEnd['date'] ?? null);

                if (! $startsAt || ! $endsAt) {
                    continue;
                }

                $snapshots[] = [
                    'user_id' => auth()->id(),
                    'calendar_id' => $calendarId,
                    'event_id' => (string) $event['id'],
                    'summary' => $event['summary'] ?? null,
                    'description' => $event['description'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'start_is_date' => isset( $eventStart['date'] ),
                    'end_is_date' => isset( $eventEnd['date'] ),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $newCount = 0;
            $userId = auth()->id();

            DB::transaction(
                static function () use ( $snapshots, $userId, $calendarId, &$newCount ): void {
                    foreach (array_chunk( $snapshots, 100 ) as $batch) {
                        $eventIds = array_column( $batch, 'event_id' );
                        $existingEventIds = CalendarEventSnapshot::query()
                            ->where( 'user_id', $userId )
                            ->where( 'calendar_id', $calendarId )
                            ->whereIn( 'event_id', $eventIds )
                            ->pluck( 'event_id' )
                            ->all();

                        $newCount += count( array_diff( $eventIds, $existingEventIds ) );

                        CalendarEventSnapshot::upsert(
                            $batch,
                            ['user_id', 'calendar_id', 'event_id'],
                            ['summary', 'description', 'starts_at', 'ends_at', 'start_is_date', 'end_is_date', 'updated_at']
                        );
                    }
                }
            );

            $message = __(
                'Calendar sync completed: :count events synced, :added new.',
                [
                'count' => count( $snapshots ),
                'added' => $newCount,
                 ]
            );

            session()->flash( 'status', $message );
            $this->dispatch( 'calendar-synced' );
        } catch (Throwable $exception) {
            report( $exception );
            session()->flash( 'error', __( 'Calendar sync failed. Please try again.' ) );
        }
    }

    /**
     * Render
     */
    public function render()
    {
        return view( 'livewire.calendar.index' )
            ->layout( 'layouts.app' );
    }
}
