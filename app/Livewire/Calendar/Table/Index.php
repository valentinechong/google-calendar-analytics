<?php

namespace App\Livewire\Calendar\Table;

use App\Models\CalendarEventSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\IncrementColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class Index extends DataTableComponent
{
    protected $model = CalendarEventSnapshot::class;

    public string $calendarId = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $query = '';
    public bool $includeDescription = false;
    public bool $exactTitle = false;
    public bool $exactDescription = false;
    public bool $pastOnly = true;

    #[On( 'calendar-synced' )]
    public function refreshAfterSync(): void
    {
        // Re-render after the parent dashboard writes updated event snapshots.
    }

    public function configure(): void
    {
        $this->setPrimaryKey( 'id' );
        $this->setDefaultPerPage( 20 );
        $this->setDefaultSort( 'starts_at', 'desc' );
        $this->setTableWrapperAttributes( ['class' => 'overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700'] );
        $this->setTableAttributes( ['class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700'] );
        $this->setThAttributes( fn () => ['class' => 'bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:bg-gray-800 dark:text-gray-300'] );
    }

    /**
     * Filters
     *
     * @return array
     */
    public function filters(): array
    {
        $timezone = config( 'app.timezone' );

        /*
         * Validate date input from HTML date filters.
         * Expected format: Y-m-d
         */

        $parseDate = static function ( mixed $value ) use ( $timezone ): ?CarbonImmutable {
            if (! is_string( $value ) || trim( $value ) === '') {
                return null;
            }

            $value = trim( $value );

            try {
                $date = CarbonImmutable::createFromFormat(
                    '!Y-m-d',
                    $value,
                    $timezone
                );

                if (! $date || $date->format( 'Y-m-d' ) !== $value) {
                    return null;
                }

                return $date;
            } catch (\Throwable) {
                return null;
            }
        };

        /*
         * Apply a date range using the date portion of the stored string.
         *
         * Supports:
         * - 2026-07-05
         * - 2026-07-05T22:00:00+08:00
         * - 2026-07-05T22:00:00Z
         */

        $applyDateRange = static function (
            Builder $builder,
            ?CarbonImmutable $start,
            ?CarbonImmutable $end
        ): void {
            if ($start !== null) {
                $builder->where(
                    'starts_at',
                    '>=',
                    $start->format( 'Y-m-d' )
                );
            }

            if ($end !== null) {
                $builder->where(
                    'starts_at',
                    '<',
                    $end->addDay()->format( 'Y-m-d' )
                );
            }
        };

        return [
            SelectFilter::make( __( 'Event type' ) )
                ->options(
                    [
                    '' => __( 'All events' ),
                    '1' => __( 'Past events only' ),
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ) use ( $timezone ): void {
                        if ($value === '1') {
                            $builder->where(
                                'ends_at',
                                '<',
                                CarbonImmutable::now( $timezone )->toIso8601String()
                            );
                        }
                    }
                ),

            SelectFilter::make( __( 'Include description' ) )
                ->options(
                    [
                    '' => __( 'Exclude description' ),
                    '1' => __( 'Include description' ),
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ): void {
                        if ($value === '1') {
                            $this->includeDescription = true;
                        }
                    }
                ),

            SelectFilter::make( __( 'Exact title match' ) )
                ->options(
                    [
                    '' => __( 'Partial match' ),
                    '1' => __( 'Exact title match' ),
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ): void {
                        if ($value === '1') {
                            $this->exactTitle = true;
                        }
                    }
                ),

            SelectFilter::make( __( 'Exact description match' ) )
                ->options(
                    [
                    '' => __( 'Partial match' ),
                    '1' => __( 'Exact description match' ),
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ): void {
                        if ($value === '1') {
                            $this->exactDescription = true;
                        }
                    }
                ),

            SelectFilter::make( __( 'Calendar ID' ) )
                ->options(
                    [
                        '' => __( 'All calendars' ),
                        $this->calendarId => $this->calendarId,
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ): void {
                        if ($value !== '') {
                            $builder->where( 'calendar_id', $value );
                        }
                    }
                ),

            SelectFilter::make( __( 'Search type' ) )
                ->options(
                    [
                    '' => __( 'Partial match' ),
                    'exact_title' => __( 'Exact title match' ),
                    'exact_description' => __( 'Exact description match' ),
                    ]
                )
                ->filter(
                    function ( Builder $builder, string $value ): void {
                        $needle = mb_strtolower( trim( $this->query ) );

                        if ($needle === '') {
                            return;
                        }

                        if ($value === 'exact_title') {
                            $builder->whereRaw(
                                'lower(summary) = ?',
                                [$needle]
                            );
                        } elseif ($value === 'exact_description') {
                            $builder->whereRaw(
                                'lower(description) = ?',
                                [$needle]
                            );
                        }
                    }
                ),

            DateFilter::make( __( 'Start date' ) )
                ->filter(
                    function (
                        Builder $builder,
                        string $value
                    ) use (
                        $parseDate,
                        $applyDateRange
                    ): void {
                        $date = $parseDate( $value );

                        if ($date !== null) {
                            $applyDateRange( $builder, $date, null );
                        }
                    }
                ),

            DateFilter::make( __( 'End date' ) )
                ->filter(
                    function (
                        Builder $builder,
                        string $value
                    ) use (
                        $parseDate,
                        $applyDateRange
                    ): void {
                        $date = $parseDate( $value );

                        if ($date !== null) {
                            $applyDateRange( $builder, null, $date );
                        }
                    }
                ),

            DateRangeFilter::make( __( 'Date range' ) )
                ->filter(
                    function (
                        Builder $builder,
                        array $value
                    ) use (
                        $parseDate,
                        $applyDateRange
                    ): void {
                        $start = $parseDate( $value['start'] ?? null );
                        $end = $parseDate( $value['end'] ?? null );

                        if ($start !== null || $end !== null) {
                            $applyDateRange( $builder, $start, $end );
                        }
                    }
                ),
        ];
    }

    public function builder(): Builder
    {
        return CalendarEventSnapshot::query()->where( 'user_id', auth()->id() );
    }

    public function columns(): array
    {
        $columns = [
            IncrementColumn::make( '#' )->excludeFromColumnSelect()->sortable(),
            Column::make( 'Event ID', 'event_id' )->excludeFromColumnSelect()->searchable()->sortable()->collapseAlways(),
            Column::make( 'Title', 'summary' )->excludeFromColumnSelect()->searchable()->sortable(),
            Column::make( 'Description', 'description' )->excludeFromColumnSelect()->searchable()->sortable()
                ->format(
                    fn ( $value ) => blank( $value ) ? '<span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-white">' . __( 'No description' ) . '</span>' : e( $value )
                )
                ->html(),
            Column::make( 'Start Time', 'starts_at' )->excludeFromColumnSelect()->sortable()
                ->format(
                    fn ( $value ) => filled( $value ) ? CarbonImmutable::parse( $value )->format( 'd M Y, h:i A' ) : '-'
                ),
            Column::make( 'End Time', 'ends_at' )->excludeFromColumnSelect()->sortable()
                ->format(
                    fn ( $value ) => filled( $value ) ? CarbonImmutable::parse( $value )->format( 'd M Y, h:i A' ) : '-'
                ),
        ];

        return $columns;
    }
}
