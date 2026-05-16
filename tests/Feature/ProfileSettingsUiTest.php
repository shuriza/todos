<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_page_shows_clear_main_sections(): void
    {
        $user = User::factory()->create([
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa@example.com',
            'google_id' => 'google-123',
            'telegram_chat_id' => '123456789',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Profil & Pengaturan', false);
        $response->assertSee('Atur data akun, koneksi Classroom, dan notifikasi Telegram.');
        $response->assertSee('Akun Mahasiswa');
        $response->assertSee('Integrasi Aplikasi');
        $response->assertSee('Preferensi Notifikasi');
        $response->assertSee('Detail Tambahan');
        $response->assertSee('Zona Bahaya');
    }
}
