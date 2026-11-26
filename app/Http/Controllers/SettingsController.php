<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function google( Request $request, EnvironmentSettings $environment ): RedirectResponse
    {
        $data = $request->validate(
            [
            'client_id' => ['required', 'string', 'max:500'],
            'client_secret' => ['required', 'string', 'max:500'],
            ]
        );
        $environment->put( ['GOOGLE_CLIENT_ID' => $data['client_id'], 'GOOGLE_CLIENT_SECRET' => $data['client_secret']] );
        return redirect()->route( 'google.connect', ['reauthorize' => 1] )->with( 'status', __( 'Google credentials saved.' ) );
    }

    public function language( Request $request ): RedirectResponse
    {
        $data = $request->validate( ['locale' => ['required', 'in:en,ms,zh_CN']] );
        $request->user()->forceFill( ['locale' => $data['locale']] )->save();
        return back()->with( 'status', __( 'Language updated.' ) );
    }

    public function calendar( Request $request, EnvironmentSettings $environment ): RedirectResponse
    {
        $data = $request->validate( ['calendar_id' => ['required', 'string', 'max:500']] );
        $environment->put( ['GOOGLE_CALENDAR_ID' => $data['calendar_id']] );
        return redirect()->route( 'dashboard' )->with( 'status', __( 'Calendar selected.' ) );
    }
}
