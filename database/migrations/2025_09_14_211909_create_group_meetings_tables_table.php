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
        Schema::create('group_meetings_tables', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_id');
            $table->string('group_id');
            $table->string('topic');
            $table->string('escription');
            $table->dateTime('meeting_date');
            $table->dateTime('meeting_time');
            $table->string('meeting_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_meetings_tables');
    }
};
