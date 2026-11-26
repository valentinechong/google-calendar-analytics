# Google Calendar Analytics

A self-hosted calendar synchronization and analytics application built with Laravel 12. The app uses Google OAuth to access a configured calendar, saves events from a selected date range as local snapshots for the signed-in user, and provides table filters, search, and duration summaries. Google Calendar remains the source of truth; local snapshots are not updated automatically.

## Contents

- [Features](#features)
- [Technology Stack and Requirements](#technology-stack-and-requirements)
- [Quick Start: Local Installation with Lando](#quick-start-local-installation-with-lando)
- [Google Calendar OAuth Setup](#google-calendar-oauth-setup)
- [Usage Workflow](#usage-workflow)
- [Production Deployment](#production-deployment)
- [Common Commands](#common-commands)
- [Environment Variables](#environment-variables)
- [Languages and Localization](#languages-and-localization)
- [Notes and Troubleshooting](#notes-and-troubleshooting)

## Features

- Google Calendar OAuth authorization, including checks that the selected calendar can be read and written.
- Sync events from a selected date and time range into the current user's local snapshots. Recurring instances expanded by Google are supported; cancelled events are ignored.
- Search snapshot titles and descriptions, choose whether to include descriptions, require exact matches, filter by calendar and date range, and filter by whether events have ended.
- View event counts, total duration, and average duration; create, edit, and delete events in Google Calendar.
- User registration, login, email verification, profile management, two-factor authentication, API token management, and per-user language settings. Availability depends on the Jetstream and Fortify configuration.

Snapshots are stored per user, calendar, and event ID. Sync adds or updates events returned by Google in the selected range. It does not remove snapshots for events deleted from Google, nor does it fetch subsequent changes automatically. Sync again to refresh existing snapshots.

## Technology Stack and Requirements

| Component | Version / Purpose |
| --- | --- |
| PHP | 8.2 or later; the Lando configuration uses PHP 8.3 |
| Laravel | 12 |
| Composer | 2 |
| Node.js / npm | Lando uses Node.js 22; Node.js 22+ is recommended for local development |
| Database | Lando uses MySQL 8.0; Laravel supports other drivers, and tests use an in-memory SQLite database |
| Front end | Livewire 3, Vite 7, Tailwind CSS 3 |
| Local environment | Lando, Docker, and Docker Compose are recommended |
| Google | A Google Cloud project, enabled Google Calendar API, OAuth Web client, and a Google account with access to the target calendar |

The application is primarily built with PHP, Blade, JavaScript, CSS, and JSON translation files. OAuth must be completed in a browser that can reach the callback URL; running the app only from the command line is not sufficient to authorize Google.

## Quick Start: Local Installation with Lando

Run these commands from the repository root. Install and start Docker Desktop and [Lando](https://lando.dev/) first.

### 1. Create the local environment and start services

```bash
cp .env.example .env
lando start
lando info
```

In Windows PowerShell, create the environment file with:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

`lando info` displays the local site URL and database connection details. The first startup may take a while while container images are downloaded.

### 2. Configure `.env`

Review or set at least the following values:

```dotenv
APP_NAME="Google Calendar Analytics"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://YOUR-APP.lndo.site
APP_TIMEZONE=Asia/Kuala_Lumpur

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REFRESH_TOKEN=
GOOGLE_CALENDAR_ID=primary
GOOGLE_REDIRECT_URI="${APP_URL}/google/callback"
```

Replace `APP_URL` with the site URL reported by `lando info`. The Laravel container should use Lando's internal database hostname and port, usually `database:3306`; do not use the host-forwarded port for `DB_PORT`. If Lando reports different database names or credentials, use those values. Set `GOOGLE_CALENDAR_ID` to `primary` or to the target calendar ID shown under **Integrate calendar** in Google Calendar settings. `APP_TIMEZONE` must be a valid PHP timezone identifier, such as `Asia/Kuala_Lumpur`.

### 3. Install dependencies, generate the key, migrate the database, and build assets

```bash
lando composer install
lando php artisan key:generate
lando php artisan migrate
lando npm install
lando npm run build
```

Open the URL shown by `lando info`, register an application user, and complete email verification if it is enabled in the current mail configuration. Then configure Google OAuth using the steps below.

For frontend development, run this in a separate terminal:

```bash
lando npm run dev -- --host 0.0.0.0
```

After changing `.lando.yml`, rebuild the environment with `lando rebuild -y`. Changes to `.env` usually do not require a rebuild; clear the configuration cache instead.

## Google Calendar OAuth Setup

### 1. Create a Google OAuth Web client

In the [Google Cloud Console](https://console.cloud.google.com/):

1. Select or create a Google Cloud project and enable the **Google Calendar API**.
2. Configure the OAuth consent screen. If the app is in testing mode, add the Google account you will use as a test user.
3. Create an OAuth client with the application type **Web application**.
4. Add the `GOOGLE_REDIRECT_URI` from `.env` to the client's **Authorized redirect URIs**, including the protocol, hostname, and path. For example: `https://YOUR-APP.lndo.site/google/callback`. The URI must match exactly.
5. Put the client ID and client secret in `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`.

### 2. Authorize the app and obtain a refresh token

After saving the configuration, run:

```bash
lando php artisan config:clear
```

Visit the application home page or dashboard as a verified user. If Google Calendar is not connected, the app will direct you to the connection page. Choose **Start connection** (or the reauthorization option) and authorize the app on Google's consent page. The app requests permission to read calendars and write events. The OAuth callback checks that the selected calendar is available to the Google account and has a writable access role. It then writes the refresh token returned by Google to the application's root `.env` file and clears Laravel's configuration cache.

Google only saves a refresh token when one is returned. If Google does not return one, start authorization again and grant offline access. Do not commit or disclose `.env`, the OAuth client secret, or the refresh token in logs or public channels. In production, the app instance handling the OAuth callback must be able to persist `.env` changes securely. If the deployment platform does not allow the app process to write `.env`, inject `GOOGLE_REFRESH_TOKEN` through a secure deployment secret manager and update that secret manually after authorization.

The Google connection is shared by the application; each signed-in user does not connect a separate Google account. `GOOGLE_CALENDAR_ID` selects the calendar the app reads and syncs. Sync again after changing it. Write operations require a Google access role that allows writes to the selected calendar.

## Usage Workflow

1. **Register and sign in:** Create a user and complete email verification if required by the environment.
2. **Connect Google Calendar:** Follow the prompt on first visit. The connection page also supports reauthorization if access has been revoked.
3. **Sync events:** On the dashboard, set a start and end time. The end time must be later than the start time. The initial range runs from the start of the current year in the configured timezone to the current time. Sync saves events as snapshots for the signed-in user.
4. **Search and filter:** Use the snapshot table to search titles and descriptions, choose exact matching, and filter by calendar, start/end date, date range, and event type. Descriptions are included in general search only when enabled. The default event type is **Past events only**; switch to all events to include upcoming events.
5. **Review analytics:** The summary reports event count, cumulative duration, and average duration for the current matching results. An event is considered past when its end time is earlier than the current time in `APP_TIMEZONE`. All-day event duration is calculated from the dates.
6. **Manage calendar events:** Users with sufficient permissions can create, edit, or delete Google Calendar events from the UI. Google Calendar remains the source of truth; sync again after changes to refresh local snapshots.

Snapshots are used for local analysis, so table searches and filters only cover synced data and do not query Google in real time. Recurring events are expanded into instances by Google, and cancelled instances are not synced.

## Production Deployment

This repository does not include deployment scripts for a specific cloud platform. It can be deployed to a PHP host or container platform that supports Laravel 12. Before going live:

1. Provide PHP 8.2+ with Laravel's required extensions, Composer 2, a Node.js/npm build environment, MySQL 8 or another supported database, and a web server whose document root points to `public/`.
2. Inject `APP_KEY`, `APP_URL`, database settings, session/cache/queue drivers, mail settings, and Google OAuth configuration into the deployment environment. Set `APP_ENV=production`, `APP_DEBUG=false`, use HTTPS, and make sure the OAuth redirect URI matches the production `APP_URL`.
3. Review `database/migrations/2026_11_24_063856_seed_admin_users.php`. This migration creates `valentine@anytine.com` with the initial password `password`. **Remove this migration or replace it with a secure initialization process before deployment.** Do not expose this fixed credential publicly. If the migration has already run, editing the migration file alone will not change the database; rotate the password or remove the account separately.
4. Build and deploy the application and frontend assets:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan optimize
   ```

   Generate or inject `APP_KEY` as required by the host, and ensure the PHP process can write to `storage/` and `bootstrap/cache/`. Do not casually change `APP_KEY` on an existing environment; doing so invalidates Laravel-encrypted data and sessions.

5. Configure a persistent database and secure backups. SQLite is not suitable for multi-instance or concurrent production use. Configure shared cache/session/queue services for the deployment topology, or explicitly choose supported single-server file/database drivers. Calendar sync does not currently require a dedicated queue worker.
6. Configure logging, mail, HTTPS, domains, and reverse proxies. Confirm the application callback URL is reachable by the browser and Google. If OAuth writes to `.env`, ensure controlled persistence and minimal write permissions for that file; otherwise, use a secrets manager to store the refresh token returned by the callback.
7. Authorize with a dedicated Google account and target calendar. Confirm the required access role, then sync a small date range to verify read/write access, timezone handling, and database writes.

Do not use the development server, `APP_DEBUG=true`, or real secrets committed to the repository in production. The app uses one shared Google connection, so all signed-in users sync against the same configured calendar. Confirm this access model is appropriate for your team before deployment.

## Common Commands

In a Lando environment, run PHP, Artisan, Composer, and npm commands through `lando` so they execute inside the containers:

| Command | Purpose |
| --- | --- |
| `lando start` / `lando stop` | Start or stop the project environment |
| `lando info` | Show the site URL, services, and port information |
| `lando rebuild -y` | Rebuild the environment after changes to `.lando.yml` |
| `lando composer install` | Install PHP dependencies |
| `lando composer dump-autoload` | Refresh Composer's autoload mappings |
| `lando php artisan key:generate` | Generate the application encryption key on first setup |
| `lando php artisan migrate` | Run database migrations |
| `lando php artisan migrate:status` | Show migration status |
| `lando php artisan migrate:rollback` | Roll back the latest migration batch; review the impact first |
| `lando php artisan config:clear` | Clear cached configuration so the latest `.env` values are loaded |
| `lando php artisan cache:clear` | Clear Laravel's cache, including a cached Google access token |
| `lando php artisan route:list` | Show registered routes |
| `lando php artisan test` | Run the PHPUnit test suite |
| `lando php artisan translations:extract` | Extract missing translation keys from source into `storage/app/private/translations/` |
| `lando php artisan translations:extract ms` | Extract keys for Malay only; supported locales are `en`, `ms`, and `zh_CN` |
| `lando php artisan user:update-password user@example.com` | Interactively update an existing user's password |
| `lando php artisan user:update-password user@example.com --force` | Update a password without Fortify's password rules; use carefully |
| `lando npm install` | Install frontend dependencies; use `lando npm ci` in CI |
| `lando npm run build` | Build production frontend assets |
| `lando npm run dev -- --host 0.0.0.0` | Start the Vite development server |
| `lando npm run sniff` | Run PHP_CodeSniffer |
| `lando npm run fix` | Automatically fix supported PHP_CodeSniffer issues |
| `lando npm run analyse` | Run PHPStan static analysis |

You can also run `lando composer test`; the Composer script clears configuration cache and runs `artisan test`. `translations:extract` extracts source strings and writes missing keys to the private output directory with the source string as the default value. It does not rewrite `lang/*.json` automatically. `user:update-password` is available when password updates are enabled in Fortify.

## Environment Variables

Laravel's `.env.example` is the default template. Do not commit your local `.env`; production values can also be supplied through environment variables or a secrets manager.

| Variable | Required | Description |
| --- | --- | --- |
| `APP_NAME` | No | Application display name |
| `APP_ENV` | Yes | Environment name; use `production` for production |
| `APP_KEY` | Yes | Laravel encryption key; store securely per environment and do not change casually |
| `APP_DEBUG` | Yes | Must be `false` in production |
| `APP_URL` | Yes | Application base URL, used to generate links and the OAuth callback |
| `APP_TIMEZONE` | Yes | PHP timezone used to parse/display dates and determine whether events have ended |
| `APP_LOCALE` | No | Default interface language; supported values are `en`, `ms`, and `zh_CN` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Yes | Laravel database connection; Lando typically uses MySQL at `database:3306` |
| `SESSION_DRIVER` | Yes | Session driver; choose one suitable for the production topology and persistence requirements |
| `CACHE_STORE` | Yes | Cache driver; Google access tokens are cached for about 50 minutes |
| `QUEUE_CONNECTION` | No | Defaults to `database`; calendar sync currently runs in the web request |
| `MAIL_MAILER`, `MAIL_*` | As needed | Mail settings; configure a working SMTP/API mail service when using email verification or password reset |
| `GOOGLE_CLIENT_ID` | Yes | Google OAuth Web client ID |
| `GOOGLE_CLIENT_SECRET` | Yes | Matching OAuth client secret |
| `GOOGLE_REFRESH_TOKEN` | Yes | Long-lived refresh token produced by OAuth; treat it as a secret |
| `GOOGLE_CALENDAR_ID` | Yes | Calendar ID used by the app; defaults to `primary` |
| `GOOGLE_REDIRECT_URI` | Yes | OAuth callback URL; must exactly match the URI in Google Cloud Console. The template defaults to `${APP_URL}/google/callback` |

Google settings are read in `config/services.php`. After changing `.env`, run `php artisan config:clear` if the environment uses cached configuration, or rebuild the configuration cache.

## Languages and Localization

The app includes `lang/en.json`, `lang/ms.json`, and `lang/zh_CN.json` for English, Malay, and Simplified Chinese. Signed-in users can select a language in their profile; guests can use the language switcher. To add translations, run `php artisan translations:extract [locale]` to generate a list of missing keys, then have a maintainer merge translations into the corresponding `lang/*.json` file.

## Notes and Troubleshooting

### Google OAuth returns `redirect_uri_mismatch`

Check that `GOOGLE_REDIRECT_URI` exactly matches the Authorized redirect URI in the Google Cloud OAuth client, including `https`, hostname, and `/google/callback` path. Update `APP_URL` as needed, then clear the configuration cache.

### Authorization succeeds, but the calendar is unavailable or not writable

Confirm that the authorizing Google account has access to the selected calendar and check `GOOGLE_CALENDAR_ID`. Read access does not necessarily include write access; creating, editing, and deleting require a Google access role that permits writes. When using `primary`, it refers to the OAuth account's primary calendar.

### Refresh token is missing, expired, or cannot be refreshed

Make sure the OAuth client ID, client secret, and refresh token belong to the same client. The OAuth request uses offline access and requests consent. Clear the cached access token (for example, with `php artisan cache:clear`), authorize again, and save the new token. If the production app cannot write to `.env`, update the deployment secret and restart or redeploy.

### Changes to `.env` do not take effect

Run `php artisan config:clear` and confirm the command is running in the correct directory and environment. If using `php artisan config:cache`, provide all required environment variables before generating the cache.

### Database connection or migration errors

Confirm the database service is running and check the connection driver, internal hostname, port, database name, and credentials in `.env`. In Lando, PHP should connect to `database:3306`; the host-forwarded port is for database clients running on the host. In production, ensure the migration database user has permission to create tables and indexes.

### Search returns no results or results appear stale

The dashboard searches the current user's synced snapshots; it does not query Google in real time. Sync the target calendar and date range again. Check whether **Past events only**, date range, description inclusion, or exact-match filters are active. Events deleted in Google are not automatically removed from existing snapshots.

### Event times or all-day events look different

Set the correct `APP_TIMEZONE` and sync again. Google all-day events are stored as dates rather than specific times, and duration is calculated as the difference between start and end dates, following Google Calendar's all-day event boundary convention. Do not substitute the server operating system timezone for the application timezone.

### Page styles or scripts are missing

Start the Vite development server during frontend development. Before deployment or static local use, run `npm run build` and confirm `public/build/manifest.json` and its build assets are deployed. The Vite development server's port must be exposed from the container to the host.

### Email verification or password reset messages are not delivered

The default `.env.example` uses the log mailer and does not send real email. Configure a working mail driver and sender address in test/production environments, then check the provider and queue logs.

### Default administrator account risk

The current migration `2026_11_24_063856_seed_admin_users.php` creates a fixed email account with an initial password visible in the source code. Remove or securely replace this initialization logic before exposing the application, and confirm there are no accounts still using the default password.

## License

MIT. See [LICENSE](LICENSE).
