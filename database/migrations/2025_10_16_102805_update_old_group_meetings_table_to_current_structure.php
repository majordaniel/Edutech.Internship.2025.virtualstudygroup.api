<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOldGroupMeetingsTableToCurrentStructure extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('group_meetings_tables', function (Blueprint $table) {
            // Drop old / unnecessary columns
            if (Schema::hasColumn('group_meetings_tables', 'meeting_id')) {
                $table->dropColumn('meeting_id');
            }

            if (Schema::hasColumn('group_meetings_tables', 'topic')) {
                $table->dropColumn('topic');
            }

            if (Schema::hasColumn('group_meetings_tables', 'escription')) {
                $table->dropColumn('escription');
            }

            // Change date and time back to correct types
            $table->date('meeting_date')->change();
            $table->time('meeting_time')->change();

            // Add new column host_id
            if (!Schema::hasColumn('group_meetings_tables', 'host_id')) {
                $table->integer('host_id')->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_meetings_tables', function (Blueprint $table) {
            // Recreate dropped columns
            $table->string('meeting_id')->nullable();
            $table->string('topic')->nullable();
            $table->string('escription')->nullable();

            // Change back types
            $table->dateTime('meeting_date')->change();
            $table->dateTime('meeting_time')->change();

            // Drop the new column
            if (Schema::hasColumn('group_meetings_tables', 'host_id')) {
                $table->dropColumn('host_id');
            }
        });
    }
}
