<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Registering, and asking for reset e-mails, is limited per address so a script cannot create
     * accounts in bulk or flood someone's inbox.
     */
    public function test_registration_and_reset_requests_are_throttled(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/register', ['name' => 'U'.$i, 'email' => "u{$i}@example.com", 'password' => 'password', 'password_confirmation' => 'password']);
            auth()->logout();
        }
        $this->post('/register', ['name' => 'U9', 'email' => 'u9@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertStatus(429);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => "x{$i}@example.com"]);
        }
        $this->post('/forgot-password', ['email' => 'x9@example.com'])->assertStatus(429);
    }
}
