<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * CalendarController
 *
 * Menangani fitur Kalender Tugas dengan tampilan bulanan.
 * Menampilkan tugas mendatang, tugas terlambat (overdue), dan
 * menyediakan API events untuk rendering kalender di sisi klien.
 *
 * Endpoints:
 *   GET /calendar        -> index()  -> Halaman kalender bulanan
 *   GET /calendar/events -> events() -> JSON data events untuk kalender (AJAX)
 */
class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Upcoming tasks (next 7 days, exclude today's overdue)
        $upcoming = Todo::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [today()->addDay(), today()->addDays(7)])
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->get();

        // Overdue (deadline sudah lewat)
        $overdue = Todo::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date')
            ->get();

        return view('calendar.index', compact(
            'upcoming', 'overdue', 'month', 'year'
        ));
    }

    public function events(Request $request)
    {
        $request->validate([
            'start' => 'nullable|date',
            'end'   => 'nullable|date',
        ]);

        $user = Auth::user();
        $start = $request->get('start', now()->startOfMonth()->toDateString());
        $end = $request->get('end', now()->endOfMonth()->toDateString());

        $todos = Todo::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->with('course')
            ->get()
            ->map(function ($todo) {
                $colors = [
                    1 => '#ef4444', // Q1 red
                    2 => '#3b82f6', // Q2 blue
                    3 => '#eab308', // Q3 yellow
                    4 => '#6b7280', // Q4 gray
                ];
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'description' => $todo->description,
                    'date' => $todo->due_date->format('Y-m-d'),
                    'due_date' => $todo->due_date->format('Y-m-d'),
                    'due_time' => $todo->due_time,
                    'status' => $todo->status,
                    'priority' => $todo->priority,
                    'kuadran' => $todo->kuadran,
                    'course' => $todo->course?->nama_course,
                    'color' => $colors[$todo->kuadran] ?? '#6b7280',
                ];
            });

        return response()->json($todos);
    }
}
