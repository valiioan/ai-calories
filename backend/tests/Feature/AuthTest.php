<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_account_profile_and_settings_and_keeps_the_session(): void
    {
        $response = $this->postJson('/register', [
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'email' => 'Ana@Example.com',
            'password' => 'parola-lunga',
            'password_confirmation' => 'parola-lunga',
        ]);

        $response->assertCreated()
            ->assertJson([
                'first_name' => 'Ana',
                'last_name' => 'Pop',
                'email' => 'ana@example.com',
            ]);

        $user = User::query()->where('email', 'ana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('parola-lunga', $user->password));
        $this->assertNotNull($user->profile);
        $this->assertSame('maintain', $user->profile->goal);
        $this->assertSame('Europe/Bucharest', $user->profile->timezone);
        $this->assertNotNull($user->settings);
        $this->assertSame('ro', $user->settings->locale);
        $this->assertAuthenticated();

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJson(['email' => 'ana@example.com']);

        $this->assertPersistentSession($response);
    }

    public function test_login_sets_a_thirty_day_cookie_and_logout_ends_the_session(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'parola-lunga',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'ana@example.com',
            'password' => 'parola-lunga',
        ]);

        $response->assertOk()->assertJson(['id' => $user->id, 'email' => 'ana@example.com']);
        $this->assertAuthenticatedAs($user);
        $this->assertPersistentSession($response);

        $this->getJson('/api/user')->assertOk()->assertJson(['id' => $user->id]);

        $this->postJson('/logout')->assertNoContent();
        Auth::forgetGuards();
        $this->assertGuest('web');
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_login_rejects_unknown_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'parola-lunga',
        ]);

        $this->postJson('/login', [
            'email' => 'ana@example.com',
            'password' => 'gresita-12',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cannot_read_the_account(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->getJson('/api/settings')->assertUnauthorized();
    }

    public function test_password_reset_logs_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/reset-password', [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'noua-parola',
            'password_confirmation' => 'noua-parola',
        ])->assertOk()->assertJson(['email' => 'ana@example.com']);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('noua-parola', $user->fresh()->password));
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->postJson('/forgot-password', ['email' => 'lipsa@example.com'])
            ->assertOk()
            ->assertJsonMissingPath('reset_url');

        Notification::assertNothingSent();
    }

    public function test_local_debug_includes_the_reset_link(): void
    {
        config(['app.expose_password_reset_url' => true]);
        User::factory()->create(['email' => 'ana@example.com']);

        $response = $this->postJson('/forgot-password', ['email' => 'ana@example.com']);

        $response->assertOk();
        $this->assertStringContainsString('/reset-password?token=', (string) $response->json('reset_url'));
        $this->assertStringContainsString('email=ana%40example.com', (string) $response->json('reset_url'));
    }
    public function test_forgot_password_sends_a_link_to_the_spa(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/forgot-password', ['email' => 'ana@example.com'])->assertOk();

        $mail = (new ResetPassword('token-clar'))->toMail($user);
        $this->assertStringContainsString('/reset-password?token=token-clar', $mail->actionUrl);
        $this->assertStringContainsString('email=ana%40example.com', $mail->actionUrl);
    }

    private function assertPersistentSession(\Illuminate\Testing\TestResponse $response): void
    {
        $session = collect($response->headers->getCookies())->first(
            fn ($cookie) => $cookie->getName() === config('session.cookie'),
        );
        $remember = collect($response->headers->getCookies())->first(
            fn ($cookie) => $cookie->getName() === Auth::guard('web')->getRecallerName(),
        );

        $this->assertNotNull($session);
        $this->assertNotNull($remember);
        $this->assertGreaterThan(now()->addDays(29)->getTimestamp(), $session->getExpiresTime());
        $this->assertGreaterThan(now()->addDays(29)->getTimestamp(), $remember->getExpiresTime());
        $this->assertLessThan(now()->addDays(31)->getTimestamp(), $session->getExpiresTime());
        $this->assertLessThan(now()->addDays(31)->getTimestamp(), $remember->getExpiresTime());
    }
}
