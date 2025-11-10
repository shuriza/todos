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
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('todo_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // 'task_breakdown', 'priority_suggestion', 'time_estimate', etc
            $table->text('suggestion');
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
    }
};
