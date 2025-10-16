<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallIdToGroupMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('group_messages', 'call_id')) {
                $table->foreignId('call_id')
                    ->nullable()
                    ->constrained('group_meetings_table') // name of the referenced table
                    ->onDelete('cascade')
                    ->after('file_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            if (Schema::hasColumn('group_messages', 'call_id')) {
                $table->dropForeign(['call_id']);
                $table->dropColumn('call_id');
            }
        });
    }
}
