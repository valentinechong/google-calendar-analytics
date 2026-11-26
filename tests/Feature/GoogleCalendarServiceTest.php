<?php

namespace Tests\Feature;

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    public function test_recurring_events_are_expanded_and_all_pages_are_fetched(): void
    {
        config( ['services.google.calendar_id' => 'primary'] );
        Cache::put( 'google-calendar-access-token', 'test-token', now()->addMinutes( 50 ) );
        $requests = [];
        Http::fake(
            function ( $request ) use ( &$requests ) {
                if (str_contains( $request->url(), '/events' )) {
                    $requests[] = $request->data();
                    return str_contains( $request->url(), 'pageToken=next' ) ? Http::response( ['items' => [['id' => 'occurrence-2', 'summary' => 'Moved instance']]] ) : Http::response( ['items' => [['id' => 'occurrence-1']], 'nextPageToken' => 'next'] );
                }
                return Http::response( [] );
            }
        );

        $events = app( GoogleCalendarService::class )->events( '2026-07-01T00:00:00+08:00', '2027-01-01T00:00:00+08:00' );

        $this->assertCount( 2, $events );
        $this->assertSame( 'Moved instance', $events[1]['summary'] );
        $this->assertCount( 2, $requests );
        $this->assertSame( 'true', $requests[0]['singleEvents'] );
    }
}
