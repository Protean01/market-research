<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add prize draw and answer-filter columns to surveys
        Schema::table('surveys', function (Blueprint $table) {
            if (! Schema::hasColumn('surveys', 'prize_draw_enabled')) {
                $table->boolean('prize_draw_enabled')->default(false)->after('response_count');
            }
            if (! Schema::hasColumn('surveys', 'target_attribute')) {
                $table->string('target_attribute')->nullable()->after('target_income_band');
            }
            if (! Schema::hasColumn('surveys', 'target_attribute_value')) {
                $table->string('target_attribute_value')->nullable()->after('target_attribute');
            }
        });

        // Prize draw entries table - update to include survey_id if it exists or create if not
        if (Schema::hasTable('prize_draw_entries')) {
            Schema::table('prize_draw_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('prize_draw_entries', 'survey_id')) {
                    $table->foreignId('survey_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                }
                if (! Schema::hasColumn('prize_draw_entries', 'points_entered')) {
                    $table->unsignedInteger('points_entered')->default(0)->after('user_id');
                }
                // Try to add unique index but it might fail if duplicates exist or if already present
                try {
                    $table->unique(['survey_id', 'user_id']);
                } catch (Exception $e) {
                }
            });
        } else {
            Schema::create('prize_draw_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('points_entered')->default(0);
                $table->timestamps();
                $table->unique(['survey_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_draw_entries');

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['prize_draw_enabled', 'target_attribute', 'target_attribute_value']);
        });
    }
};
