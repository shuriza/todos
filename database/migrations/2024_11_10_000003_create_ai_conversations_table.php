<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('todo_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('message');
            $table->json('metadata')->nullable(); // Store token count, model used, etc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
