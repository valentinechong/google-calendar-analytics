<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarService
{
    public function authorizationUrl( string $state ): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(
            [
            'client_id' => config( 'services.google.client_id' ),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            ]
        );
    }

    public function exchangeCode( string $code ): array
    {
        return Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
            'code' => $code,
            'client_id' => config( 'services.google.client_id' ),
            'client_secret' => config( 'services.google.client_secret' ),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            ]
        )->throw()->json();
    }

    public function events( string $timeMin, string $timeMax, ?string $calendarId = null ): array
    {
        $events = [];
        $pageToken = null;
        do {
            $response = $this->request()->get(
                $this->calendarUrl( $calendarId ),
                array_filter(
                    [
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 2500,
                    'pageToken' => $pageToken,
                    ],
                    static fn ( $value ) => $value !== null
                )
            );
            $data = $response->throw()->json();
            array_push( $events, ...($data['items'] ?? []) );
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $events;
    }

    public function calendars(): array
    {
        $items = [];
        $pageToken = null;
        do {
            $data = $this->request()->get( 'https://www.googleapis.com/calendar/v3/users/me/calendarList', array_filter( ['maxResults' => 250, 'showHidden' => 'true', 'pageToken' => $pageToken] ) )->throw()->json();
            array_push( $items, ...($data['items'] ?? []) );
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);
        return $items;
    }

    public function calendarDetails( string $calendarId ): array
    {
        return $this->request()->get( 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $calendarId ) )->throw()->json();
    }

    /**
     * Verify that Google accepts reads and reports write access for the selected calendar.
     */
    public function verifyReadWriteAccess(): array
    {
        $calendars = $this->calendars();
        $calendarId = (string) config( 'services.google.calendar_id', 'primary' );
        $calendar = $calendarId === 'primary' ? collect( $calendars )->firstWhere( 'primary', true ) : collect( $calendars )->firstWhere( 'id', $calendarId );

        if (!$calendar) {
            throw new RuntimeException( 'The selected Google Calendar is not available to this account.' );
        }

        if (!in_array( $calendar['accessRole'] ?? null, ['owner', 'writer', 'writerWithoutPrivateAccess'], true )) {
            throw new RuntimeException( 'The selected Google Calendar does not allow event writes.' );
        }

        return $calendars;
    }

    public function create( array $event, ?string $calendarId = null ): array
    {
        return $this->request()->post( $this->calendarUrl( $calendarId ), $event )->throw()->json();
    }

    public function update( string $eventId, array $event, ?string $calendarId = null ): array
    {
        return $this->request()->patch( $this->calendarUrl( $calendarId ) . '/' . rawurlencode( $eventId ), $event )->throw()->json();
    }

    public function delete( string $eventId, ?string $calendarId = null ): void
    {
        $this->request()->delete( $this->calendarUrl( $calendarId ) . '/' . rawurlencode( $eventId ) )->throw();
    }

    private function request(): PendingRequest
    {
        $token = Cache::remember(
            'google-calendar-access-token',
            now()->addMinutes( 50 ),
            function (): string {
                $refreshToken = config( 'services.google.refresh_token' );
                if (!$refreshToken || !config( 'services.google.client_id' ) || !config( 'services.google.client_secret' )) {
                    throw new RuntimeException( 'Google Calendar is not configured.' );
                }

                $response = Http::asForm()->post(
                    'https://oauth2.googleapis.com/token',
                    [
                    'client_id' => config( 'services.google.client_id' ),
                    'client_secret' => config( 'services.google.client_secret' ),
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                    ]
                )->throw()->json();
                return $response['access_token'];
            }
        );

        return Http::withToken( $token )->acceptJson()->timeout( 20 );
    }

    private function calendarUrl( ?string $calendarId = null ): string
    {
        return 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $calendarId ?? (string) config( 'services.google.calendar_id' ) ) . '/events';
    }

    private function redirectUri(): string
    {
        return config( 'services.google.redirect_uri' ) ?: route( 'google.callback' );
    }
}
