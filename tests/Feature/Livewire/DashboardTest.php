<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_without_google_configuration_are_sent_to_connect(): void
    {
        $this->actingAs( User::factory()->create() )
            ->get( '/dashboard' )
            ->assertRedirect( route( 'google.connect' ) );
    }
}
