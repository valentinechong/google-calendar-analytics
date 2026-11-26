<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\SettingsController;
use App\Livewire\Calendar\Index as CalendarIndex;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    function () {
        return view( 'welcome' );
    }
);

Route::middleware(
    [
    'auth:sanctum',
    config( 'jetstream.auth_session' ),
    'verified',
    ]
)->group(
    function () {
        Route::get( '/dashboard', CalendarIndex::class )->name( 'dashboard' );
        Route::get( '/calendars/{calendarId}', [GoogleCalendarController::class, 'calendarDetails'] )->name( 'calendar.details' );

        Route::get( '/google/connect', [GoogleCalendarController::class, 'connect'] )->name( 'google.connect' );
        Route::get( '/google/callback', [GoogleCalendarController::class, 'callback'] )->name( 'google.callback' );
        Route::post( '/calendar/events', [GoogleCalendarController::class, 'store'] )->name( 'calendar.events.store' );
        Route::put( '/calendar/events/{eventId}', [GoogleCalendarController::class, 'update'] )->name( 'calendar.events.update' );
        Route::delete( '/calendar/events/{eventId}', [GoogleCalendarController::class, 'destroy'] )->name( 'calendar.events.destroy' );
        Route::post( '/settings/google', [SettingsController::class, 'google'] )->name( 'settings.google' );
        Route::post( '/settings/language', [SettingsController::class, 'language'] )->name( 'settings.language' );
        Route::post( '/settings/calendar', [SettingsController::class, 'calendar'] )->name( 'settings.calendar' );
    }
);

Route::middleware( 'web' )->get(
    '/locale/{locale}',
    function ( string $locale ) {
        abort_unless( in_array( $locale, ['en', 'ms', 'zh_CN'], true ), 404 );
        session( ['locale' => $locale] );
        return back();
    }
)->name( 'locale.switch' );
