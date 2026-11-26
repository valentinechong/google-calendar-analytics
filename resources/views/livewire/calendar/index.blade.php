<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
            {{ __('Calendar dashboard') }}
        </h2>
    </x-slot>
    @if (session()->has('status'))
        <div role="status" class="inline-flex rounded-full bg-green-800 px-4 py-2 text-sm font-semibold text-white shadow-sm dark:bg-green-700 dark:text-white">
            {{ session('status') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div role="alert" class="inline-flex rounded-full bg-red-800 px-4 py-2 text-sm font-semibold text-white shadow-sm dark:bg-red-700 dark:text-white">
            {{ session('error') }}
        </div>
    @endif
    <details class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Sync range') }}</summary>
        <div class="space-y-4 border-t border-gray-200 p-5 dark:border-gray-700">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="syncStartAt" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From') }}</label>
                    <input id="syncStartAt" type="datetime-local" step="1" wire:model="syncStartAt" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @error('syncStartAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="syncEndAt" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('To') }}</label>
                    <input id="syncEndAt" type="datetime-local" step="1" wire:model="syncEndAt" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @error('syncEndAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <x-button type="button" wire:click="syncCalendars" wire:loading.attr="disabled" wire:target="syncCalendars">
                <span wire:loading.remove wire:target="syncCalendars">{{ __('Sync Calendars') }}</span>
                <span wire:loading wire:target="syncCalendars">{{ __('Syncing...') }}</span>
            </x-button>
        </div>
    </details>
    <section class="space-y-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <livewire:calendar.table.index />
    </section>
</div>
