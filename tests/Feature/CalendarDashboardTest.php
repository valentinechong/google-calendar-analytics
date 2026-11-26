<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_exceptions_are_counted_and_cancelled_instances_are_hidden(): void
    {
        config( ['app.timezone' => 'Asia/Kuala_Lumpur', 'services.google.refresh_token' => 'refresh-token'] );
        Cache::put( 'google-calendar-access-token', 'access-token', now()->addMinutes( 50 ) );
        Http::fake(
            fn () => Http::response(
                [
                'items' => [
                [
                    'id' => 'modified-instance',
                'summary' => 'Focus session',
                'description' => '',
                    'recurringEventId' => 'series-1',
                'originalStartTime' => ['dateTime' => '2026-07-02T09:00:00+08:00'],
                    'start' => ['dateTime' => '2026-07-02T10:00:00+08:00'],
                'end' => ['dateTime' => '2026-07-02T10:45:00+08:00'],
                ],
                ['id' => 'cancelled-instance', 'status' => 'cancelled', 'recurringEventId' => 'series-1'],
                ],
                ]
            )
        );

        $response = $this->actingAs( User::factory()->create() )->get( '/dashboard?preset=custom&start=2026-07-01&end=2026-07-31&q=Focus' );

        $response->assertOk()->assertSee( 'Focus session' )->assertDontSee( 'cancelled-instance' );
        Http::assertSent(
            fn ( $request ) => str_contains( $request->url(), 'singleEvents=true' )
            && str_contains( $request->url(), 'timeMin=2026-07-01T00%3A00%3A00%2B08%3A00' )
            && str_contains( $request->url(), 'timeMax=2026-08-01T00%3A00%3A00%2B08%3A00' )
        );
    }
}
