<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ __('Google Calendar connection') }}</h2>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800 dark:text-gray-100">
            @if (session('error'))
                <div role="alert"
                    class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                    {{ session('error') }}</div>
            @endif
            @if (!config('services.google.client_id') || !config('services.google.client_secret'))
                <h3 class="mb-2 text-lg font-semibold">{{ __('Connect Google Calendar') }}</h3>
                <p class="mb-5 text-gray-600 dark:text-gray-300">
                    {{ __('Enter your Google OAuth client credentials to begin. They will be saved to this installation.') }}
                </p>
                <form method="POST" action="{{ route('settings.google') }}" class="space-y-4">@csrf
                    <label class="block text-sm font-medium">{{ __('Google Client ID') }}<input name="client_id"
                            required autocomplete="off"
                            class="mt-1 w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></label>
                    <label class="block text-sm font-medium">{{ __('Google Client Secret') }}<input name="client_secret"
                            type="password" required autocomplete="new-password"
                            class="mt-1 w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></label>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save and continue') }}</button>
                </form>
            @else
                <h3 class="mb-2 text-lg font-semibold">{{ __('Google Calendar access needs attention') }}</h3>
                <p class="mb-5 text-gray-600 dark:text-gray-300">
                    {{ __('The dashboard is paused until Google Calendar can be read and the selected calendar allows event changes. Select the button below to verify access.') }}
                </p>
                <a class="inline-block rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    href="{{ route('google.connect', ['reauthorize' => 1]) }}">{{ __('Reconnect Google Calendar') }}</a>
            @endif
        </div>
    </div>
</x-app-layout>
