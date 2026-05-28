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
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedInteger('current_streak')->default(0)->after('is_complete');
            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');
            $table->timestamp('last_survey_at')->nullable()->after('longest_streak');
            $table->unsignedInteger('trust_score')->default(100)->after('last_survey_at'); // 0-100
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['current_streak', 'longest_streak', 'last_survey_at', 'trust_score']);
        });
    }
};
