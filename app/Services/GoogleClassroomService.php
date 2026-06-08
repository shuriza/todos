<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoogleClassroomService
 *
 * Service untuk integrasi Google Classroom API.
 * Melakukan sinkronisasi mata kuliah (courses) dan tugas (coursework)
 * dari akun Google Classroom pengguna, termasuk auto-refresh token
 * ketika access token sudah kedaluwarsa.
 *
 * Fitur: Google Classroom Sync
 *
 * Method utama:
 *  - hasAccess()       Cek apakah user punya akses Google Classroom yang valid
 *  - apiGet()          Request GET ke Classroom API dengan auto-refresh token
 *  - syncCourses()     Sinkronisasi daftar mata kuliah dari Classroom
 *  - syncCoursework()  Sinkronisasi tugas/coursework ke tabel todos
 *  - refreshToken()    Perbarui access token menggunakan refresh token
 */
class GoogleClassroomService
{
    protected User $user;
    protected string $baseUrl = 'https://classroom.googleapis.com/v1';

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Check if user has valid Google Classroom access.
     */
    public function hasAccess(): bool
    {
        return !empty($this->user->google_access_token);
    }

    /**
     * Get authorization headers for API calls.
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->user->google_access_token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Make an authenticated GET request to Google Classroom API.
     * Automatically refreshes token if expired.
     */
    protected function apiGet(string $endpoint, array $params = [], bool $throwOnError = true): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . $endpoint, $params);

        // If token expired, try to refresh
        if ($response->status() === 401 && $this->user->google_refresh_token) {
            $this->refreshAccessToken();
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . $endpoint, $params);
        }

        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? '';
            $errorStatus = $errorBody['error']['status'] ?? '';

            Log::warning('Google Classroom API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            // If throwOnError is false, return empty array instead of throwing
            if (!$throwOnError) {
                return [];
            }

            // Provide user-friendly error messages
            if ($response->status() === 403) {
                if (str_contains($errorMessage, 'API has not been used') || str_contains($errorMessage, 'disabled')) {
                    throw new \Exception(
                        'Google Classroom API belum diaktifkan di project Google. Silakan aktifkan di Google Cloud Console.'
                    );
                }
                if (str_contains($errorStatus, 'PERMISSION_DENIED') || str_contains($errorMessage, 'permission')) {
                    throw new \Exception(
                        'Izin Google Classroom belum diberikan. Silakan klik tombol "Hubungkan Ulang Google" di bawah untuk memberikan izin akses Classroom.'
                    );
                }
            }

            if ($response->status() === 401) {
                throw new \Exception(
                    'Token Google kadaluarsa dan gagal diperbarui. Silakan klik "Hubungkan Ulang Google" untuk login ulang.'
                );
            }

            throw new \Exception('Google Classroom API error: ' . $response->status() . ' - ' . ($errorMessage ?: $response->body()));
        }

        return $response->json() ?? [];
    }

    /**
     * Refresh the Google access token using the refresh token.
     */
    protected function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $this->user->google_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->user->update([
                'google_access_token' => $data['access_token'],
            ]);
            $this->user->refresh();

            Log::info('Google token refreshed for user ' . $this->user->id);
        } else {
            Log::error('Failed to refresh Google token', [
                'user_id' => $this->user->id,
                'response' => $response->body(),
            ]);
            throw new \Exception('Gagal memperbarui token Google. Silakan login ulang dengan Google.');
        }
    }

    /**
     * Fetch all active courses from Google Classroom.
     */
    public function fetchCourses(): array
    {
        $allCourses = [];
        $pageToken = null;

        do {
            $params = [
                'courseStates' => 'ACTIVE',
                'pageSize' => 30,
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->apiGet('/courses', $params);
            $courses = $response['courses'] ?? [];
            $allCourses = array_merge($allCourses, $courses);
            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken);

        return $allCourses;
    }

    /**
     * Fetch coursework (assignments) for a specific course.
     */
    public function fetchCoursework(string $courseId): array
    {
        $allWork = [];
        $pageToken = null;

        do {
            $params = [
                'pageSize' => 30,
                'orderBy' => 'dueDate desc',
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->apiGet("/courses/{$courseId}/courseWork", $params);
            $work = $response['courseWork'] ?? [];
            $allWork = array_merge($allWork, $work);
            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken);

        return $allWork;
    }

    /**
     * Fetch student submissions for a coursework item.
     * Uses throwOnError=false because teacher/owner accounts may not have
     * student submissions (they're not students), which returns 403.
     */
    public function fetchSubmissions(string $courseId, string $courseworkId): array
    {
        $response = $this->apiGet(
            "/courses/{$courseId}/courseWork/{$courseworkId}/studentSubmissions",
            ['userId' => 'me'],
            false // Don't throw on error — teacher accounts will get 403 here
        );

        return $response['studentSubmissions'] ?? [];
    }

    /**
     * Sync courses from Google Classroom to local database.
     * Returns count of synced courses.
     */
    public function syncCourses(): array
    {
        $googleCourses = $this->fetchCourses();
        $synced = 0;
        $existing = 0;

        // Color palette for courses
        $colors = ['#4285F4', '#EA4335', '#FBBC04', '#34A853', '#FF6D01', '#46BDC6', '#7B1FA2', '#C2185B'];

        foreach ($googleCourses as $index => $gc) {
            $course = Course::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'google_course_id' => $gc['id'],
                ],
                [
                    'nama_course' => $gc['name'] ?? 'Unknown Course',
                    'deskripsi_ruang' => trim(($gc['section'] ?? '') . ' ' . ($gc['room'] ?? '')),
                    'color' => $colors[$index % count($colors)],
                ]
            );

            if ($course->wasRecentlyCreated) {
                $synced++;
            } else {
                $existing++;
            }
        }

        return [
            'total' => count($googleCourses),
            'synced' => $synced,
            'existing' => $existing,
        ];
    }

    /**
     * Sync coursework (assignments) from all Google Classroom courses into todos.
     * Returns count of synced tasks.
     */
    public function syncAllCoursework(): array
    {
        $courses = Course::where('user_id', $this->user->id)
            ->whereNotNull('google_course_id')
            ->get();

        Log::info('Syncing coursework for user ' . $this->user->id, [
            'courses_count' => $courses->count(),
            'course_ids' => $courses->pluck('google_course_id')->toArray(),
        ]);

        $totalSynced = 0;
        $totalSkipped = 0;
        $totalUpdated = 0;
        $errors = [];

        foreach ($courses as $course) {
            try {
                $result = $this->syncCourseworkForCourse($course);
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalUpdated += $result['updated'];

                Log::info("Synced coursework for course: {$course->nama_course}", $result);
            } catch (\Exception $e) {
                Log::error("Failed to sync coursework for course: {$course->nama_course}", [
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "{$course->nama_course}: {$e->getMessage()}";
            }
        }

        if (!empty($errors) && $totalSynced === 0 && $totalUpdated === 0) {
            throw new \Exception('Gagal sync tugas: ' . implode('; ', $errors));
        }

        return [
            'synced' => $totalSynced,
            'skipped' => $totalSkipped,
            'updated' => $totalUpdated,
            'errors' => $errors,
        ];
    }

    /**
     * Sync coursework for a single course.
     */
    public function syncCourseworkForCourse(Course $course): array
    {
        $coursework = $this->fetchCoursework($course->google_course_id);
        
        Log::info("Course '{$course->nama_course}' has " . count($coursework) . " coursework items");
        $synced = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($coursework as $cw) {
            // Parse due date
            $dueDate = null;
            $dueTime = null;

            if (isset($cw['dueDate']) && isset($cw['dueDate']['year'], $cw['dueDate']['month'], $cw['dueDate']['day'])) {
                $year = $cw['dueDate']['year'];
                $month = str_pad($cw['dueDate']['month'], 2, '0', STR_PAD_LEFT);
                $day = str_pad($cw['dueDate']['day'], 2, '0', STR_PAD_LEFT);
                $dueDate = "{$year}-{$month}-{$day}";

                if (isset($cw['dueTime'])) {
                    $hours = (int) ($cw['dueTime']['hours'] ?? 0);
                    $minutes = (int) ($cw['dueTime']['minutes'] ?? 0);

                    // Google Classroom returns dueTime in UTC — convert to app timezone
                    $utcDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        "{$dueDate} " . str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT),
                        'UTC'
                    )->setTimezone(config('app.timezone', 'Asia/Jakarta'));

                    // Update dueDate in case timezone conversion crosses midnight
                    $dueDate = $utcDateTime->format('Y-m-d');
                    $dueTime = $utcDateTime->format('H:i');
                }
            }

            // Determine status - check submissions
            // Default to 'todo' (valid DB enum: todo, in_progress, completed, unfinished)
            $status = 'todo';
            $submissions = $this->fetchSubmissions($course->google_course_id, $cw['id']);
            foreach ($submissions as $sub) {
                $subState = $sub['state'] ?? '';
                if (in_array($subState, ['TURNED_IN', 'RETURNED'])) {
                    $status = 'completed';
                    break;
                } elseif ($subState === 'CREATED') {
                    $status = 'todo'; // Assigned but not started
                }
            }

            // Auto-detect "tidak terselesaikan" (reversible).
            // Google Classroom API tidak mengekspos status "ditutup", sehingga
            // kita menyimpulkannya: tugas yang BELUM dikirim (bukan completed)
            // dan sudah lewat tenggat melebihi masa tenggang dianggap tidak
            // terselesaikan. Bersifat reversible — keputusan manual mahasiswa
            // (status_locked) tidak akan ditimpa pada sinkronisasi berikutnya.
            $graceDays = (int) config('todos.unfinished_grace_days', 1);
            $autoUnfinished = false;
            if ($graceDays >= 0 && $status !== 'completed' && $dueDate) {
                $cutoff = Carbon::parse($dueDate)->endOfDay()->addDays($graceDays);
                $autoUnfinished = now()->greaterThan($cutoff);
            }

            // Prioritas tugas Classroom selalu "high" (Penting) karena tugas
            // dari dosen bersifat penting secara inheren. Dimensi urgensi
            // (dari deadline) yang menentukan kuadran K1 vs K2 melalui
            // Todo::hitungKuadran(). Pengguna dapat menurunkan prioritas manual.
            $priority = 'high';

            // Calculate kuadran Eisenhower
            $kuadran = Todo::hitungKuadran($priority, $dueDate);

            // Sync without downgrading local status
            $existingTodo = Todo::where('user_id', $this->user->id)
                ->where('google_task_id', $cw['id'])
                ->first();

            $data = [
                'course_id' => $course->id,
                'title' => $cw['title'] ?? 'Untitled Assignment',
                'description' => $cw['description'] ?? null,
                'priority' => $priority,
                'kuadran' => $kuadran,
                'sumber' => 'google_classroom',
                'google_link' => $cw['alternateLink'] ?? null,
                'due_date' => $dueDate,
                'due_time' => $dueTime,
            ];

            if ($existingTodo) {
                // Hormati keputusan manual: jika status dikunci mahasiswa,
                // sinkronisasi tidak menyentuh status sama sekali (reversible).
                if (!$existingTodo->status_locked) {
                    // Upgrade ke completed bila Classroom menyatakan sudah dikirim.
                    if ($status === 'completed' && $existingTodo->status !== 'completed') {
                        $data['status'] = 'completed';
                        $data['completed_at'] = now();
                    } elseif (
                        $autoUnfinished
                        && !in_array($existingTodo->status, ['completed', 'unfinished'], true)
                    ) {
                        // Auto-tandai tidak terselesaikan (belum dikirim & lewat tenggat).
                        $data['status'] = 'unfinished';
                        $data['completed_at'] = now();
                    }
                }

                if ($existingTodo->status === 'completed' && !$existingTodo->completed_at) {
                    $data['completed_at'] = now();
                }

                // Check if anything actually changed
                $hasChanges = false;
                foreach ($data as $key => $value) {
                    if ($this->hasTodoAttributeChanged($existingTodo, $key, $value)) {
                        $hasChanges = true;
                        break;
                    }
                }

                if ($hasChanges) {
                    $existingTodo->update($data);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                // Tugas baru: bila sudah lewat tenggat & belum dikirim, langsung
                // tandai tidak terselesaikan; selain itu pakai status dari Classroom.
                $newStatus = ($autoUnfinished && $status !== 'completed') ? 'unfinished' : $status;
                $data['status'] = $newStatus;
                if (in_array($newStatus, ['completed', 'unfinished'], true)) {
                    $data['completed_at'] = now();
                }
                $data['user_id'] = $this->user->id;
                $data['google_task_id'] = $cw['id'];
                Todo::create($data);
                $synced++;
            }
        }

        if ($synced > 0 || $updated > 0) {
            \App\Support\TodoCache::flush($this->user->id);
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'updated' => $updated,
        ];
    }

    /**
     * Check whether a synced Classroom value is materially different from the local todo value.
     */
    protected function hasTodoAttributeChanged(Todo $todo, string $key, mixed $value): bool
    {
        $current = $todo->getAttribute($key);

        if ($key === 'due_date') {
            $current = $todo->due_date?->format('Y-m-d');
        }

        if ($key === 'due_time') {
            $current = $current ? substr((string) $current, 0, 5) : null;
            $value = $value ? substr((string) $value, 0, 5) : null;
        }

        return $current != $value;
    }

    /**
     * Get sync summary for the user.
     */
    public function getSyncSummary(): array
    {
        $courses = Course::where('user_id', $this->user->id)->count();
        $classroomTasks = Todo::where('user_id', $this->user->id)
            ->where('sumber', 'google_classroom')
            ->count();
        $pendingClassroom = Todo::where('user_id', $this->user->id)
            ->where('sumber', 'google_classroom')
            ->whereNotIn('status', ['completed', 'unfinished'])
            ->count();

        return [
            'courses' => $courses,
            'total_tasks' => $classroomTasks,
            'pending_tasks' => $pendingClassroom,
        ];
    }
}
