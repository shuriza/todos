<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk dengan Akun Kampus');
        $response->assertSee(route('auth.google'), false);
        $response->assertDontSee('name="password"', false);
    }

    public function test_google_login_redirect_route_is_available(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_google_callback_creates_user_default_categories_and_authenticates(): void
    {
        $googleUser = (new SocialiteUser())->map([
            'id' => 'google-123',
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
        $googleUser->token = 'access-token';
        $googleUser->refreshToken = 'refresh-token';
        $googleUser->accessTokenResponseBody = [
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/classroom.courses.readonly',
                'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
                'https://www.googleapis.com/auth/classroom.coursework.students.readonly',
                'https://www.googleapis.com/auth/classroom.student-submissions.me.readonly',
                'https://www.googleapis.com/auth/classroom.student-submissions.students.readonly',
            ]),
        ];

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(User::where('email', 'mahasiswa@example.com')->first());
        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa@example.com',
            'google_id' => 'google-123',
        ]);

        foreach (DefaultCategoriesSeeder::DEFAULT_CATEGORIES as $category) {
            $this->assertDatabaseHas('categories', [
                'name' => $category['name'],
                'user_id' => User::where('email', 'mahasiswa@example.com')->value('id'),
            ]);
        }
    }

    public function test_google_callback_updates_existing_user_without_duplicating_default_categories(): void
    {
        $user = User::factory()->create([
            'email' => 'mahasiswa@example.com',
            'google_id' => 'old-google-id',
            'google_refresh_token' => 'existing-refresh-token',
        ]);

        Category::create([
            'user_id' => $user->id,
            'name' => 'Kuliah',
            'color' => '#6366f1',
            'icon' => null,
            'order' => 1,
        ]);

        $googleUser = (new SocialiteUser())->map([
            'id' => 'google-456',
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa@example.com',
            'avatar' => 'https://example.com/new-avatar.jpg',
        ]);
        $googleUser->token = 'new-access-token';
        $googleUser->refreshToken = null;
        $googleUser->accessTokenResponseBody = [];

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('google-456', $user->fresh()->google_id);
        $this->assertSame('existing-refresh-token', $user->fresh()->google_refresh_token);
        $this->assertSame(1, Category::where('user_id', $user->id)->where('name', 'Kuliah')->count());
    }

    public function test_email_password_login_post_route_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(405);
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
