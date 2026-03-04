<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel courses sesuai ERD.
     * Menyimpan data mata kuliah dari Google Classroom.
     * Relasi: users (1:M) → courses
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('google_course_id')->nullable()->index();
            $table->string('nama_course');
            $table->string('deskripsi_ruang')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->timestamps();

            // Unique constraint: satu user tidak bisa punya course duplikat dari Google Classroom
            $table->unique(['user_id', 'google_course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
