<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                    $hours = str_pad($cw['dueTime']['hours'] ?? 0, 2, '0', STR_PAD_LEFT);
                    $minutes = str_pad($cw['dueTime']['minutes'] ?? 0, 2, '0', STR_PAD_LEFT);
                    $dueTime = "{$hours}:{$minutes}";
                }
            }

            // Determine status - check submissions
            // Default to 'todo' (valid DB enum: todo, in_progress, completed)
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

            // Determine priority based on due date
            $priority = 'medium';
            if ($dueDate) {
                $daysUntil = now()->diffInDays(Carbon::parse($dueDate), false);
                if ($daysUntil < 0) {
                    $priority = 'high'; // Overdue
                } elseif ($daysUntil <= 3) {
                    $priority = 'high'; // Urgent
                } elseif ($daysUntil <= 7) {
                    $priority = 'medium';
                } else {
                    $priority = 'low';
                }
            }

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
                'due_date' => $dueDate,
                'due_time' => $dueTime,
            ];

            if ($existingTodo) {
                // Only upgrade status, never downgrade:
                // If Classroom says completed but local isn't, upgrade.
                // If local is already completed but Classroom says todo, keep completed.
                if ($status === 'completed' && $existingTodo->status !== 'completed') {
                    $data['status'] = 'completed';
                    $data['completed_at'] = now();
                }

                $existingTodo->update($data);
                $updated++;
            } else {
                $data['status'] = $status;
                $data['user_id'] = $this->user->id;
                $data['google_task_id'] = $cw['id'];
                Todo::create($data);
                $synced++;
            }
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'updated' => $updated,
        ];
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
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'courses' => $courses,
            'total_tasks' => $classroomTasks,
            'pending_tasks' => $pendingClassroom,
        ];
    }
}
