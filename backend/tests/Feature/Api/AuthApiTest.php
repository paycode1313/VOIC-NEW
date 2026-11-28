<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Same rule as the web login: five wrong passwords for one email from one address, then a wait,
     * so a password cannot be guessed through the mobile API. Other accounts on the same campus Wi-Fi are not blocked.
     */
    public function test_guessing_a_password_through_the_api_is_slowed_down(): void
    {
        $user = User::factory()->create(['email' => 'rina@example.com']);
        User::factory()->create(['email' => 'budi@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'salah'.$i])
                ->assertStatus(422);
        }

        $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'password'])
            ->assertStatus(429)
            ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'detik'));

        $this->postJson(route('api.v1.auth.login'), ['email' => 'budi@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_correct_login_clears_earlier_mistakes(): void
    {
        User::factory()->create(['email' => 'rina@example.com']);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'password'])->assertOk();

        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson(route('api.v1.auth.login'), ['email' => 'rina@example.com', 'password' => 'password'])->assertOk();
    }

    public function test_mass_registration_from_one_address_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.auth.register'), ['name' => 'U'.$i, 'email' => "u{$i}@example.com", 'password' => 'Rahasia123!'])
                ->assertCreated();
        }

        $this->postJson(route('api.v1.auth.register'), ['name' => 'U9', 'email' => 'u9@example.com', 'password' => 'Rahasia123!'])
            ->assertStatus(429);
    }
}
