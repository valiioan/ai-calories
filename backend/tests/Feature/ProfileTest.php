<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_is_created_when_missing_and_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJson([
                'birth_date' => null,
                'goal' => 'maintain',
                'timezone' => 'Europe/Bucharest',
            ]);

        $this->putJson('/api/profile', [
                'birth_date' => '1994-04-12',
                'height_cm' => 168.5,
                'activity_level' => 'light',
                'goal' => 'lose',
                'calorie_target' => 2200,
                'timezone' => 'Europe/Paris',
            ])
            ->assertOk()
            ->assertJson([
                'birth_date' => '1994-04-12',
                'height_cm' => 168.5,
                'activity_level' => 'light',
                'goal' => 'lose',
                'calorie_target' => 2200,
                'timezone' => 'Europe/Paris',
            ]);
    }

    public function test_profile_changes_stay_on_the_authenticated_user(): void
    {
        $ana = User::factory()->create();
        $bogdan = User::factory()->create();
        $anaProfile = $ana->ensureProfile();
        $bogdan->ensureProfile();

        $this->postJson('/login', [
            'email' => $ana->email,
            'password' => 'password',
        ])->assertOk();

        $this->putJson('/api/profile', ['timezone' => 'UTC', 'goal' => 'gain'])
            ->assertOk();

        $this->assertSame('UTC', $ana->profile()->first()->timezone);
        $this->assertSame('Europe/Bucharest', $bogdan->profile()->first()->timezone);
        $this->assertFalse($bogdan->can('update', $anaProfile));
        $this->assertFalse($bogdan->can('view', $anaProfile));
    }

    public function test_account_name_and_email_can_be_updated(): void
    {
        $user = User::factory()->create(['email' => 'vechi@example.com']);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->putJson('/api/user', [
            'first_name' => 'Ana',
            'last_name' => 'Nouă',
            'email' => 'Nou@Example.com',
        ])
            ->assertOk()
            ->assertJson([
                'first_name' => 'Ana',
                'last_name' => 'Nouă',
                'email' => 'nou@example.com',
            ]);
    }
}
