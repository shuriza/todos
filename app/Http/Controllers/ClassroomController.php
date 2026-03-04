<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Todo;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    /**
     * Display the Google Classroom integration page.
     */
    public function index()
    {
        $user = Auth::user();
        
        $courses = Course::where('user_id', $user->id)
            ->withCount(['todos', 'todos as pending_todos_count' => function ($q) {
                $q->where('status', '!=', 'completed');
            }])
            ->orderBy('nama_course')
            ->get();

        $classroomTodos = Todo::where('user_id', $user->id)
            ->where('sumber', 'google_classroom')
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get();

        $stats = [
            'total_courses' => $courses->count(),
            'total_tasks' => $classroomTodos->count(),
            'pending' => $classroomTodos->where('status', '!=', 'completed')->count(),
            'completed' => $classroomTodos->where('status', 'completed')->count(),
            'overdue' => $classroomTodos->filter(fn($t) => $t->isOverdue())->count(),
        ];

        $hasGoogleAccess = !empty($user->google_access_token);

        return view('classroom.index', compact('courses', 'classroomTodos', 'stats', 'hasGoogleAccess'));
    }

    /**
     * Sync courses from Google Classroom.
     */
    public function syncCourses()
    {
        $user = Auth::user();

        if (empty($user->google_access_token)) {
            return back()->with('error', 'Anda belum menghubungkan akun Google. Silakan login ulang dengan Google.');
        }

        try {
            $service = new GoogleClassroomService($user);
            $result = $service->syncCourses();

            $message = "Sinkronisasi berhasil! {$result['synced']} mata kuliah baru ditambahkan.";
            if ($result['existing'] > 0) {
                $message .= " {$result['existing']} mata kuliah diperbarui.";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Sync tasks/assignments from all Google Classroom courses.
     */
    public function syncTasks()
    {
        $user = Auth::user();

        if (empty($user->google_access_token)) {
            return back()->with('error', 'Anda belum menghubungkan akun Google. Silakan login ulang dengan Google.');
        }

        try {
            $service = new GoogleClassroomService($user);
            
            // Sync courses first
            $service->syncCourses();
            
            // Then sync tasks
            $result = $service->syncAllCoursework();

            $message = "Sinkronisasi tugas berhasil! {$result['synced']} tugas baru ditambahkan.";
            if ($result['updated'] > 0) {
                $message .= " {$result['updated']} tugas diperbarui.";
            }
            if ($result['synced'] === 0 && $result['updated'] === 0) {
                $message = "Sinkronisasi selesai. Tidak ada tugas baru ditemukan di Google Classroom. Pastikan kelas memiliki assignment/tugas yang sudah dipublish.";
            }
            if (!empty($result['errors'])) {
                $message .= " (Beberapa error: " . implode(', ', $result['errors']) . ")";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show detail for a single course and its tasks.
     */
    public function showCourse(Course $course)
    {
        $this->authorize('view', $course);

        $todos = Todo::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'completed')")
            ->orderBy('due_date', 'asc')
            ->get();

        $stats = [
            'total' => $todos->count(),
            'pending' => $todos->where('status', 'todo')->count(),
            'in_progress' => $todos->where('status', 'in_progress')->count(),
            'completed' => $todos->where('status', 'completed')->count(),
            'overdue' => $todos->filter(fn($t) => $t->isOverdue())->count(),
        ];

        return view('classroom.course', compact('course', 'todos', 'stats'));
    }

    /**
     * Remove a synced course (and optionally its tasks).
     */
    public function destroyCourse(Course $course)
    {
        $this->authorize('delete', $course);

        // Delete associated classroom tasks
        Todo::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->where('sumber', 'google_classroom')
            ->delete();

        $course->delete();

        return back()->with('success', "Mata kuliah '{$course->nama_course}' berhasil dihapus.");
    }
}
