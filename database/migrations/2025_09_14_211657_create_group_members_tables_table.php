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
        Schema::create('group_members_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained('study_groups')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('course_code');
            $table->string('role')->default('member');
            $table->timestamps();
            $table->unique(['study_group_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members_tables');
    }
};
