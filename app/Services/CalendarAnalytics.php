<?php

namespace App\Services;

use Carbon\Carbon;

class CalendarAnalytics
{
    public function summarize( array $events ): array
    {
        $minutes = array_sum( array_map( fn ( array $event ) => $this->durationMinutes( $event ), $events ) );

        return [
            'count' => count( $events ),
            'minutes' => $minutes,
            'average' => count( $events ) ? (int) round( $minutes / count( $events ) ) : 0,
        ];
    }

    public function durationMinutes( array $event ): int
    {
        $start = $this->dateTime( $event['start'] ?? [] );
        $end = $this->dateTime( $event['end'] ?? [] );
        if (!$start || !$end || $end->lessThanOrEqualTo( $start )) {
            return 0;
        }

        return (int) $start->diffInMinutes( $end );
    }

    public function hasEnded( array $event, Carbon $now ): bool
    {
        $end = $this->dateTime( $event['end'] ?? [] );
        return $end !== null && $end->lessThan( $now );
    }

    private function dateTime( array $value ): ?Carbon
    {
        $raw = $value['dateTime'] ?? ($value['date'] ?? null);
        if (!$raw) {
            return null;
        }

        return Carbon::parse( $raw, $value['timeZone'] ?? config( 'app.timezone' ) )
            ->setTimezone( config( 'app.timezone' ) );
    }
}
