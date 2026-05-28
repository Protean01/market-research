<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->boolean('is_winner')->default(false)->after('points_entered');
            $table->timestamp('won_at')->nullable()->after('is_winner');
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->integer('time_taken')->nullable()->after('earned_points'); // in seconds
            $table->boolean('is_flagged')->default(false)->after('time_taken');
            $table->string('flag_reason')->nullable()->after('is_flagged');
            $table->integer('quality_score')->default(100)->after('flag_reason');
        });
    }

    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->dropColumn(['is_winner', 'won_at']);
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn(['time_taken', 'is_flagged', 'flag_reason', 'quality_score']);
        });
    }
};
