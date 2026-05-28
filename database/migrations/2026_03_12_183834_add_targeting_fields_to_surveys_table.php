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
        Schema::table('surveys', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('status');
            $table->unsignedInteger('response_cap')->default(0)->after('is_active');
            $table->unsignedInteger('response_count')->default(0)->after('response_cap');
            $table->unsignedInteger('reward_points')->default(0)->after('reward_amount');
            $table->json('questions')->nullable()->after('description');
            $table->json('enrichment_questions')->nullable()->after('questions');
            $table->string('target_gender')->nullable()->after('status');
            $table->string('target_age_band')->nullable()->after('target_gender');
            $table->string('target_location')->nullable()->after('target_age_band');
            $table->string('target_language')->nullable()->after('target_location');
            $table->string('target_employment')->nullable()->after('target_language');
            $table->string('target_income_band')->nullable()->after('target_employment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'response_cap',
                'response_count',
                'reward_points',
                'questions',
                'enrichment_questions',
                'target_gender',
                'target_age_band',
                'target_location',
                'target_language',
                'target_employment',
                'target_income_band',
            ]);
        });
    }
};
