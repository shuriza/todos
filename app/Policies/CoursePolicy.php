<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Policy otorisasi untuk mata kuliah (course).
 * Memastikan user hanya bisa mengakses course miliknya sendiri.
 *
 * Fitur terkait: Google Classroom
 */
class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }
}
