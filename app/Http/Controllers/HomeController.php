<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Calculate statistics
        $stats = [
            'total' => Todo::where('user_id', $user->id)->count(),
            'completed' => Todo::where('user_id', $user->id)->where('status', 'completed')->count(),
            'pending' => Todo::where('user_id', $user->id)->where('status', '!=', 'completed')->count(),
            'overdue' => Todo::where('user_id', $user->id)->incomplete()->overdue()->count(),
            'classroom' => Todo::where('user_id', $user->id)->fromClassroom()->count(),
            'courses' => Course::where('user_id', $user->id)->count(),
        ];
        
        // Get tasks by Kuadran Eisenhower (using new kuadran field)
        $urgentImportant = Todo::where('user_id', $user->id)
            ->where('kuadran', Todo::KUADRAN_DO_NOW)
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get();
            
        $notUrgentImportant = Todo::where('user_id', $user->id)
            ->where('kuadran', Todo::KUADRAN_SCHEDULE)
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get();
            
        $urgentNotImportant = Todo::where('user_id', $user->id)
            ->where('kuadran', Todo::KUADRAN_DELEGATE)
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get();

        $notUrgentNotImportant = Todo::where('user_id', $user->id)
            ->where('kuadran', Todo::KUADRAN_ELIMINATE)
            ->where('status', '!=', 'completed')
            ->with('course')
            ->orderBy('due_date', 'asc')
            ->get();

        // Fallback: if no kuadran assigned yet, use old priority-based logic
        if ($urgentImportant->isEmpty() && $notUrgentImportant->isEmpty() && $urgentNotImportant->isEmpty() && $notUrgentNotImportant->isEmpty()) {
            $urgentImportant = Todo::where('user_id', $user->id)
                ->where('priority', 'high')
                ->where('status', '!=', 'completed')
                ->get();

            $notUrgentImportant = Todo::where('user_id', $user->id)
                ->where('priority', 'medium')
                ->where('status', '!=', 'completed')
                ->get();

            $urgentNotImportant = Todo::where('user_id', $user->id)
                ->where('priority', 'low')
                ->where('status', '!=', 'completed')
                ->get();
        }
        
        return view('home', compact(
            'stats',
            'urgentImportant', 'notUrgentImportant',
            'urgentNotImportant', 'notUrgentNotImportant'
        ));
    }
}
