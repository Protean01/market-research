<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('prize_draw_entries', 'survey_id')) {
                $table->foreignId('survey_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('prize_draw_entries', 'user_id')) {
                $table->foreignId('user_id')->after('survey_id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('prize_draw_entries', 'points_entered')) {
                $table->unsignedInteger('points_entered')->default(0)->after('user_id');
            }

            if (! Schema::hasColumn('prize_draw_entries', 'is_winner')) {
                $table->boolean('is_winner')->default(false)->after('points_entered');
            }

            if (! Schema::hasColumn('prize_draw_entries', 'won_at')) {
                $table->timestamp('won_at')->nullable()->after('is_winner');
            }

            // Make sure we have a unique constraint for a user per survey draw
            try {
                $table->unique(['survey_id', 'user_id'], 'prize_draw_survey_user_unique');
            } catch (Throwable $e) {
                // ignore if duplicates exist; migration should still continue
            }
        });
    }

    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            try {
                $table->dropUnique('prize_draw_survey_user_unique');
            } catch (Throwable $e) {
                // ignore if index not present
            }
        });
    }
};
