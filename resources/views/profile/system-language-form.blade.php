
<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        {{ __('System language') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Update your account\'s system language.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="locale" value="{{ __('Language') }}" />

            <select
                id="locale"
                wire:model="state.locale"
                required
                autocomplete="locale"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            >
                @foreach ([
                    'en' => __('English'),
                    'ms' => __('Melayu'),
                    'zh_CN' => __('中文'),
                ] as $code => $label)
                    <option value="{{ $code }}">
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <x-input-error for="state.locale" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button
            wire:loading.attr="disabled"
            wire:target="updateProfileInformation"
        >
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>
