<?php

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

/**
 * Policy otorisasi untuk percakapan AI.
 * Memastikan user hanya bisa mengakses riwayat chat AI miliknya sendiri.
 *
 * Fitur terkait: Asisten Pintar
 */
class AiConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }

    public function delete(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }
}
