<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-700 hover:underline dark:text-indigo-400">← {{ __('Dashboard') }}</a>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ __('Calendar details') }}</h2>
        </div>
    </x-slot>
    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <p class="text-sm font-medium text-indigo-700 dark:text-indigo-400">{{ __('Google Calendar') }}</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $calendar['summary'] ?? $calendar['id'] }}</h1>
                @if (!empty($calendar['description']))
                    <p class="mt-3 text-gray-600 dark:text-gray-300">{{ $calendar['description'] }}</p>
                @endif
            </div>
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ([__('Calendar ID') => ($calendar['id'] ?? ''), __('Time zone') => ($calendar['timeZone'] ?? __('Unknown')), __('Access') => ($calendar['accessRole'] ?? __('Unknown')), __('Location') => ($calendar['location'] ?? __('Not specified'))] as $label => $value)
                    <div class="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="break-all text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </main>
</x-app-layout>
