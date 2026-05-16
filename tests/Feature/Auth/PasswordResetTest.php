<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_is_not_available(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }

    public function test_reset_password_link_post_route_is_not_available(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertNotFound();
        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_is_not_available(): void
    {
        $response = $this->get('/reset-password/token-contoh');

        $response->assertNotFound();
    }

    public function test_reset_password_post_route_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'token-contoh',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
    }
}
