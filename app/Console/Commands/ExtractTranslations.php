<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtractTranslations extends Command
{
    protected $signature = 'translations:extract {locale? : The locale code for the JSON file (defaults to en, ms, and zh_CN)}';

    protected $description = 'Extract translation strings and merge them into a locale JSON file in storage';

    public function handle( Filesystem $files ): int
    {
        $locale = $this->argument( 'locale' );
        $locales = $locale === null ? ['en', 'ms', 'zh_CN'] : [$locale];
        $result = self::SUCCESS;

        foreach ($locales as $locale) {
            if ($this->extractLocale( $locale, $files ) !== self::SUCCESS) {
                $result = self::FAILURE;
            }
        }

        return $result;
    }

    private function extractLocale( string $locale, Filesystem $files ): int
    {
        $sourcePath = lang_path( $locale . '.json' );

        if (! preg_match( '/^[A-Za-z0-9_-]+$/', $locale )) {
            $this->error( 'The locale may only contain letters, numbers, underscores, and hyphens.' );

            return self::FAILURE;
        }

        $translations = [];

        if ($files->exists( $sourcePath )) {
            $translations = json_decode( $files->get( $sourcePath ), true );

            if (! is_array( $translations )) {
                $this->error( "The language file [{$sourcePath}] does not contain a valid JSON object." );

                return self::FAILURE;
            }
        }

        $existingCount = count( $translations );
        $addedCount = 0;
        $sourceDirectories = [
            app_path(),
            base_path( 'bootstrap' ),
            base_path( 'config' ),
            base_path( 'database' ),
            base_path( 'resources' ),
            base_path( 'routes' ),
            base_path( 'tests' ),
        ];
        $supportedExtensions = [ 'php', 'js', 'jsx', 'ts', 'tsx', 'vue' ];
        $pattern = '~(?:__|trans|@lang)\s*\(\s*([\'\"])((?:\\\\.|(?!\1)[\s\S])*)\1~';

        foreach ($sourceDirectories as $directory) {
            if (! $files->isDirectory( $directory )) {
                continue;
            }

            foreach ($files->allFiles( $directory ) as $file) {
                if (! in_array( strtolower( $file->getExtension() ), $supportedExtensions, true )) {
                    continue;
                }

                $contents = $files->get( $file->getPathname() );

                if (! preg_match_all( $pattern, $contents, $matches, PREG_SET_ORDER )) {
                    continue;
                }

                foreach ($matches as $match) {
                    $key = $this->decodeLiteral( $match[1], $match[2] );

                    if ($key === '' || array_key_exists( $key, $translations )) {
                        continue;
                    }

                    // Keep the source string as the value; translation is intentionally left to a person.
                    $translations[$key] = $key;
                    $addedCount++;
                }
            }
        }

        $outputDirectory = storage_path( 'app/private/translations' );
        $files->ensureDirectoryExists( $outputDirectory );
        $outputPath = $outputDirectory . DIRECTORY_SEPARATOR . $locale . '.json';
        $json = json_encode( $translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

        if ($json === false || $files->put( $outputPath, $json . PHP_EOL ) === false) {
            $this->error( "Could not write the generated language file to [{$outputPath}]." );

            return self::FAILURE;
        }

        $this->info( "Generated [{$outputPath}] with {$existingCount} existing keys and {$addedCount} new keys." );

        return self::SUCCESS;
    }

    private function decodeLiteral( string $quote, string $literal ): string
    {
        if ($quote === "'") {
            return preg_replace_callback(
                '/\\\\([\\\\\'])/',
                static fn ( array $match ): string => $match[1],
                $literal
            ) ?? $literal;
        }

        return stripcslashes( $literal );
    }
}
