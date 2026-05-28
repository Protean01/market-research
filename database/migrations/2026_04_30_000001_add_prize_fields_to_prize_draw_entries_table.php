<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            // Which slot in survey->prizes[] this entry won (null = unawarded / legacy single prize)
            $table->unsignedInteger('prize_index')->nullable()->after('won_at');
            // Snapshot of the prize at time of winning so changes to survey don't affect history
            $table->json('prize_snapshot')->nullable()->after('prize_index');
        });
    }

    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->dropColumn(['prize_index', 'prize_snapshot']);
        });
    }
};
