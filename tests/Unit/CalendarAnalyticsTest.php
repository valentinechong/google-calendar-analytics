<?php

namespace Tests\Unit;

use App\Services\CalendarAnalytics;
use Carbon\Carbon;
use Tests\TestCase;

class CalendarAnalyticsTest extends TestCase
{
    private CalendarAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();
        config( ['app.timezone' => 'Asia/Kuala_Lumpur'] );
        $this->analytics = new CalendarAnalytics();
    }

    public function test_completed_event_is_counted_as_past(): void
    {
        $event = $this->event( '2026-11-26T09:00:00+08:00', '2026-11-26T10:00:00+08:00' );
        $this->assertTrue( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T10:00:01+08:00' ) ) );
        $this->assertSame( 60, $this->analytics->durationMinutes( $event ) );
    }

    public function test_ongoing_event_is_not_past(): void
    {
        $event = $this->event( '2026-11-26T09:00:00+08:00', '2026-11-26T11:00:00+08:00' );
        $this->assertFalse( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T10:00:00+08:00' ) ) );
    }

    public function test_future_event_is_not_past(): void
    {
        $event = $this->event( '2026-11-27T09:00:00+08:00', '2026-11-27T10:00:00+08:00' );
        $this->assertFalse( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T10:00:00+08:00' ) ) );
    }

    public function test_event_ending_exactly_now_is_not_past(): void
    {
        $event = $this->event( '2026-11-26T09:00:00+08:00', '2026-11-26T10:00:00+08:00' );
        $this->assertFalse( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T10:00:00+08:00' ) ) );
    }

    public function test_cross_midnight_event_uses_full_end_timestamp(): void
    {
        $event = $this->event( '2026-11-25T23:30:00+08:00', '2026-11-26T00:30:00+08:00' );
        $this->assertFalse( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T00:00:00+08:00' ) ) );
        $this->assertTrue( $this->analytics->hasEnded( $event, Carbon::parse( '2026-11-26T00:30:01+08:00' ) ) );
        $this->assertSame( 60, $this->analytics->durationMinutes( $event ) );
    }

    public function test_each_recurring_instance_is_counted_as_an_occurrence(): void
    {
        $events = [
            $this->event( '2026-07-01T09:00:00+08:00', '2026-07-01T09:30:00+08:00' ),
            $this->event( '2026-07-02T09:00:00+08:00', '2026-07-02T09:30:00+08:00' ),
            $this->event( '2026-07-03T09:00:00+08:00', '2026-07-03T09:30:00+08:00' ),
        ];
        $this->assertSame( ['count' => 3, 'minutes' => 90, 'average' => 30], $this->analytics->summarize( $events ) );
    }

    private function event( string $start, string $end ): array
    {
        return [
            'start' => ['dateTime' => $start],
            'end' => ['dateTime' => $end],
        ];
    }
}
