@if ($event->id)
    <div class="flex gap-3 whitespace-nowrap">
        <button type="button" onclick="document.getElementById('edit-event-{{ $event->id }}').showModal()"
            class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Edit') }}</button>
        <button type="button" onclick="document.getElementById('delete-event-{{ $event->id }}').showModal()"
            class="text-red-600 hover:underline dark:text-red-400">{{ __('Delete') }}</button>
    </div>
    <dialog id="edit-event-{{ $event->id }}"
        class="w-full max-w-xl rounded-xl bg-white p-0 text-gray-900 shadow-xl backdrop:bg-gray-950/60 dark:bg-gray-800 dark:text-gray-100">
        <form method="POST" action="{{ route('calendar.events.update', ['eventId' => $event->event_id]) }}"
            class="grid grid-cols-2 gap-4 p-5">@csrf @method('PUT')<input type="hidden" name="calendar_id" value="{{ $event->calendar_id }}"><h3 class="col-span-2 text-lg font-semibold">
                {{ __('Edit event') }}</h3>
            <label class="col-span-2 text-sm">{{ __('Title') }}<input name="summary" value="{{ $event->summary }}"
                    required
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"></label>
            <label class="col-span-2 text-sm">{{ __('Description') }}
                <textarea name="description" rows="3"
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $event->description }}</textarea>
            </label>
            <label class="text-sm">{{ __('Starts') }}<input name="start"
                    type="{{ $event->start_is_date ? 'date' : 'datetime-local' }}"
                    value="{{ $event->start_is_date ? $event->starts_at : \Carbon\Carbon::parse($event->starts_at)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') }}"
                    required
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"></label>
            <label class="text-sm">{{ __('Ends') }}<input name="end"
                    type="{{ $event->end_is_date ? 'date' : 'datetime-local' }}"
                    value="{{ $event->end_is_date ? $event->ends_at : \Carbon\Carbon::parse($event->ends_at)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') }}"
                    required
                    class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"></label>
            <div class="col-span-2 flex justify-end gap-3"><button type="button"
                    onclick="this.closest('dialog').close()"
                    class="rounded-lg border px-4 py-2">{{ __('Cancel') }}</button><button
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save') }}</button></div>
        </form>
    </dialog>
    <dialog id="delete-event-{{ $event->id }}"
        class="w-full max-w-md rounded-xl bg-white p-6 text-center text-gray-900 shadow-xl backdrop:bg-gray-950/60 dark:bg-gray-800 dark:text-gray-100">
        <h3 class="mb-3 text-lg font-semibold">{{ __('Delete event?') }}</h3>
        <p class="mb-6 text-gray-600 dark:text-gray-300">{{ __('This action cannot be undone.') }}</p>
        <form method="POST" action="{{ route('calendar.events.destroy', ['eventId' => $event->event_id]) }}"
            class="flex justify-center gap-3">@csrf @method('DELETE')<input type="hidden" name="calendar_id" value="{{ $event->calendar_id }}"><button type="button"
                onclick="this.closest('dialog').close()"
                class="rounded-lg border px-4 py-2">{{ __('Cancel') }}</button><button
                class="rounded-lg bg-red-600 px-4 py-2 text-white">{{ __('Yes, delete') }}</button></form>
    </dialog>
@else
    <span class="text-sm text-gray-500">{{ __('Actions unavailable for this event.') }}</span>
@endif
