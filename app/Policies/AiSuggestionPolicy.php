<?php

namespace App\Policies;

use App\Models\AiSuggestion;
use App\Models\User;

/**
 * Policy otorisasi untuk saran AI.
 * Memastikan user hanya bisa mengakses saran AI yang ditujukan untuk tugasnya.
 *
 * Fitur terkait: Asisten Pintar
 */
class AiSuggestionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiSuggestion $suggestion): bool
    {
        return $user->id === $suggestion->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiSuggestion $suggestion): bool
    {
        return $user->id === $suggestion->user_id;
    }

    public function delete(User $user, AiSuggestion $suggestion): bool
    {
        return $user->id === $suggestion->user_id;
    }
}
