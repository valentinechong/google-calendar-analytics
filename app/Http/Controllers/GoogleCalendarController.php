<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use App\Services\EnvironmentSettings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function __construct( private GoogleCalendarService $calendar )
    {
    }

    public function calendarDetails( string $calendarId ): View
    {
        $calendars = $this->calendar->calendars();
        $calendar = collect( $calendars )->first( fn ( array $item ) => ($item['id'] ?? null) === $calendarId && ($item['accessRole'] ?? null) === 'owner' );
        abort_unless( $calendar, 404 );

        return view( 'calendar.details', ['calendar' => $this->calendar->calendarDetails( $calendarId )] );
    }

    public function connect( Request $request ): RedirectResponse|View
    {
        if (!config( 'services.google.client_id' ) || !config( 'services.google.client_secret' )) {
            return view( 'calendar.connect' );
        }

        if (!$request->boolean( 'reauthorize' )) {
            return view( 'calendar.connect' );
        }

        if ($request->boolean( 'reauthorize' )) {
            cache()->forget( 'google-calendar-access-token' );
        }

        $state = bin2hex( random_bytes( 24 ) );
        $request->session()->put( 'google_oauth_state', $state );
        return redirect()->away( $this->calendar->authorizationUrl( $state ) );
    }

    public function callback( Request $request ): View|RedirectResponse
    {
        if ($request->has( 'error' )) {
            return redirect()->route( 'google.connect' )->with( 'error', __( 'Google authorization was not completed.' ) );
        }

        abort_unless(
            $request->filled( 'state' ) && hash_equals( (string) $request->session()->pull( 'google_oauth_state' ), (string) $request->query( 'state' ) ),
            419,
            'Invalid Google OAuth state.'
        );

        try {
            $tokens = $this->calendar->exchangeCode( (string) $request->query( 'code' ) );
            Cache::forget( 'google-calendar-access-token' );
            if (!empty( $tokens['refresh_token'] )) {
                app( EnvironmentSettings::class )->put( ['GOOGLE_REFRESH_TOKEN' => $tokens['refresh_token']] );
            }
            $this->calendar->verifyReadWriteAccess();
        } catch (Throwable $exception) {
            report( $exception );
            Cache::forget( 'google-calendar-access-token' );
            return redirect()->route( 'google.connect' )->with( 'error', __( 'Google authorization failed. Reconnect Google Calendar and try again.' ) );
        }

        return redirect()->route( 'dashboard' )->with( 'status', __( 'Google Calendar connected and verified.' ) );
    }

    public function store( Request $request ): RedirectResponse
    {
        $calendarId = $this->validatedOwnerCalendar( $request );
        $data = $this->validateEvent( $request );
        $this->calendar->create( $data, $calendarId );
        return back()->with( 'status', __( 'Event created.' ) );
    }

    public function update( Request $request, string $eventId ): RedirectResponse
    {
        $calendarId = $this->validatedOwnerCalendar( $request );
        $this->calendar->update( $eventId, $this->validateEvent( $request ), $calendarId );
        return back()->with( 'status', __( 'Event updated.' ) );
    }

    public function destroy( Request $request, string $eventId ): RedirectResponse
    {
        $calendarId = $this->validatedOwnerCalendar( $request );
        $this->calendar->delete( $eventId, $calendarId );
        return back()->with( 'status', __( 'Event deleted.' ) );
    }

    private function validateEvent( Request $request ): array
    {
        $data = $request->validate(
            [
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            ]
        );
        $timezone = config( 'app.timezone' );
        return [
            'summary' => $data['summary'],
            'description' => $data['description'] ?? '',
            'start' => str_contains( $data['start'], 'T' ) ? ['dateTime' => Carbon::parse( $data['start'], $timezone )->toIso8601String(), 'timeZone' => $timezone] : ['date' => $data['start']],
            'end' => str_contains( $data['end'], 'T' ) ? ['dateTime' => Carbon::parse( $data['end'], $timezone )->toIso8601String(), 'timeZone' => $timezone] : ['date' => $data['end']],
        ];
    }

    private function validatedOwnerCalendar( Request $request ): string
    {
        $data = $request->validate( ['calendar_id' => ['required', 'string', 'max:500']] );
        $calendar = collect( $this->calendar->calendars() )->first(
            fn ( array $item ) => ($item['id'] ?? null) === $data['calendar_id'] && ($item['accessRole'] ?? null) === 'owner'
        );
        abort_unless( $calendar, 403 );

        return $data['calendar_id'];
    }
}
