<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_created_for_the_current_user_and_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJson([
                'locale' => 'ro',
                'appearance' => 'light',
                'units' => 'metric',
                'notify_meals' => true,
                'notify_weight' => false,
                'notify_weekly' => true,
            ]);

        $this->putJson('/api/settings', [
                'locale' => 'en',
                'appearance' => 'dark',
                'units' => 'imperial',
                'notify_meals' => false,
                'notify_weight' => true,
                'notify_weekly' => false,
            ])
            ->assertOk()
            ->assertJson([
                'locale' => 'en',
                'appearance' => 'dark',
                'units' => 'imperial',
                'notify_meals' => false,
                'notify_weight' => true,
                'notify_weekly' => false,
            ]);
    }

    public function test_settings_changes_do_not_cross_users(): void
    {
        $ana = User::factory()->create();
        $bogdan = User::factory()->create();
        $anaSettings = $ana->ensureSettings();
        $bogdan->ensureSettings();

        $this->postJson('/login', [
            'email' => $ana->email,
            'password' => 'password',
        ])->assertOk();

        $this->putJson('/api/settings', ['locale' => 'en'])
            ->assertOk();

        $this->assertSame('en', $ana->settings()->first()->locale);
        $this->assertSame('ro', $bogdan->settings()->first()->locale);
        $this->assertFalse($bogdan->can('update', $anaSettings));
    }
}
