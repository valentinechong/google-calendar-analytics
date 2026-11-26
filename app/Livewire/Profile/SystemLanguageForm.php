<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class SystemLanguageForm extends Component
{
    public array $state = [];

    public function mount(): void
    {
        $this->state['locale'] = Auth::user()->locale ?? 'en';
    }

    public function updateProfileInformation(): void
    {
        $validated = $this->validate(
            [
            'state.locale' => [
                'required',
                'string',
                Rule::in( ['en', 'ms', 'zh_CN'] ),
            ],
            ]
        );

        $locale = $validated['state']['locale'];

        Auth::user()->update(
            [
            'locale' => $locale,
            ]
        );

        App::setLocale( $locale );
        session( ['locale' => $locale] );

        $this->redirect( route( 'profile.show' ), navigate: false );
    }

    public function render()
    {
        return view( 'profile.system-language-form' );
    }
}
