<button type="button" onclick="document.getElementById('create-event-modal').showModal()"
    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800">{{ __('Create event') }}</button>
<dialog id="create-event-modal"
    class="w-full max-w-xl rounded-xl bg-white p-0 text-gray-900 shadow-xl backdrop:bg-gray-950/60 dark:bg-gray-800 dark:text-gray-100">
    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <div class="flex justify-between">
            <h3 class="text-lg font-semibold">{{ __('Create an event') }}</h3><button type="button"
                onclick="this.closest('dialog').close()" aria-label="{{ __('Close') }}">×</button>
        </div>
    </div>
    <form method="POST" action="{{ route('calendar.events.store') }}" class="grid grid-cols-2 gap-4 p-5">@csrf<input type="hidden" name="calendar_id" value="{{ $calendarId }}">
        <label class="col-span-2 text-sm">{{ __('Title') }}<input name="summary" required
                class="mt-1 w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></label>
        <label class="col-span-2 text-sm">{{ __('Description') }}
            <textarea name="description" rows="3"
                class="mt-1 w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
        </label>
        <label class="text-sm">{{ __('Starts') }}<input name="start" type="datetime-local" required
                class="mt-1 w-full rounded-lg border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700"></label>
        <label class="text-sm">{{ __('Ends') }}<input name="end" type="datetime-local" required
                class="mt-1 w-full rounded-lg border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700"></label>
        <div class="col-span-2 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700"><button
                type="button" onclick="this.closest('dialog').close()"
                class="rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600">{{ __('Cancel') }}</button><button
                class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save') }}</button></div>
    </form>
</dialog>
