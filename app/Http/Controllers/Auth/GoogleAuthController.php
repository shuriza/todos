<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Required Google Classroom scopes.
     */
    protected static array $classroomScopes = [
        'https://www.googleapis.com/auth/classroom.courses.readonly',
        'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
        'https://www.googleapis.com/auth/classroom.coursework.students.readonly',
        'https://www.googleapis.com/auth/classroom.student-submissions.me.readonly',
        'https://www.googleapis.com/auth/classroom.student-submissions.students.readonly',
    ];

    /**
     * Redirect to Google for authentication.
     * Request additional scopes for Google Classroom access.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(self::$classroomScopes)
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    /**
     * Handle Google callback.
     * Stores Google tokens for Classroom API access.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check which scopes were actually granted
            $grantedScopes = $googleUser->approvedScopes ?? [];
            $hasClassroomAccess = empty($grantedScopes) || // If scopes not available, assume granted
                collect(self::$classroomScopes)->every(fn($s) => in_array($s, $grantedScopes));
            
            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(60)),
                    'email_verified_at' => now(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'google_access_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                ]);
            } else {
                // Update Google tokens on every login
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'google_access_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token,
                ]);
            }
            
            // Log the user in
            Auth::login($user, true);

            Log::info('Google login success', [
                'user_id' => $user->id,
                'has_classroom_access' => $hasClassroomAccess,
                'has_refresh_token' => !empty($googleUser->refreshToken),
            ]);

            // Redirect based on context
            $redirect = session('google_reconnect') ? route('classroom.index') : route('home');
            session()->forget('google_reconnect');
            
            return redirect($redirect)->with(
                $hasClassroomAccess ? 'success' : 'warning',
                $hasClassroomAccess 
                    ? 'Berhasil login dengan Google! Akses Classroom aktif.'
                    : 'Login berhasil, tapi izin Google Classroom mungkin belum diberikan. Coba hubungkan ulang.'
            );
            
        } catch (\Exception $e) {
            Log::error('Google login failed', ['error' => $e->getMessage()]);
            
            // If user is already logged in (reconnecting), redirect back to classroom
            if (Auth::check()) {
                return redirect()->route('classroom.index')
                    ->with('error', 'Gagal menghubungkan Google: ' . $e->getMessage());
            }
            
            return redirect()->route('login')
                ->withErrors(['error' => 'Gagal login dengan Google. Silakan coba lagi. ' . $e->getMessage()]);
        }
    }

    /**
     * Reconnect Google account (for authenticated users).
     * Forces re-consent to ensure Classroom scopes are granted.
     */
    public function reconnect()
    {
        session(['google_reconnect' => true]);
        
        return Socialite::driver('google')
            ->scopes(self::$classroomScopes)
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'login_hint' => Auth::user()->email,
            ])
            ->redirect();
    }

    /**
     * Check if user's token has valid Classroom access.
     * Returns JSON for AJAX calls.
     */
    public function checkAccess()
    {
        $user = Auth::user();
        
        if (empty($user->google_access_token)) {
            return response()->json([
                'has_access' => false,
                'reason' => 'no_token',
                'message' => 'Akun Google belum terhubung.',
            ]);
        }

        // Try a simple Classroom API call to verify access
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $user->google_access_token,
                'Accept' => 'application/json',
            ])->get('https://classroom.googleapis.com/v1/courses', [
                'pageSize' => 1,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'has_access' => true,
                    'message' => 'Akses Google Classroom aktif.',
                ]);
            }

            $error = $response->json();
            $errorMessage = $error['error']['message'] ?? 'Unknown error';
            $errorCode = $error['error']['code'] ?? $response->status();

            return response()->json([
                'has_access' => false,
                'reason' => $errorCode == 401 ? 'token_expired' : 'permission_denied',
                'message' => $errorCode == 401 
                    ? 'Token Google kadaluarsa. Silakan hubungkan ulang.'
                    : 'Izin Classroom belum diberikan. Silakan hubungkan ulang akun Google dengan memberikan izin Classroom.',
                'details' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'has_access' => false,
                'reason' => 'error',
                'message' => 'Gagal memeriksa akses: ' . $e->getMessage(),
            ]);
        }
    }
}
