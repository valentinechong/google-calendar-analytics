<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class EnvironmentSettings
{
    public function put( array $values ): void
    {
        $path = base_path( '.env' );
        $contents = File::exists( $path ) ? File::get( $path ) : '';

        foreach ($values as $key => $value) {
            $encoded = '"' . addcslashes( (string) $value, "\\\"\n\r" ) . '"';
            $line = $key . '=' . $encoded;
            $pattern = '/^\s*#?\s*' . preg_quote( $key, '/' ) . '\s*=.*$/m';
            if (preg_match( $pattern, $contents )) {
                $contents = preg_replace( $pattern, $line, $contents, 1 );
            } else {
                $contents = rtrim( $contents ) . "\n" . $line . "\n";
            }
            config()->set(
                'services.google.' . match ($key) {
                'GOOGLE_CLIENT_ID' => 'client_id', 'GOOGLE_CLIENT_SECRET' => 'client_secret',
                'GOOGLE_REFRESH_TOKEN' => 'refresh_token', 'GOOGLE_CALENDAR_ID' => 'calendar_id',
                },
                (string) $value
            );
        }

        File::put( $path, $contents );
        Artisan::call( 'config:clear' );
    }
}
