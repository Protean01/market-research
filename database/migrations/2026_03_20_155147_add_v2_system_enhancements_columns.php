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
        // Users: Tracking if they've set a password
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_password_set')->default(false)->after('password');
        });

        // Surveys: Fixed Reward Type
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('reward_type')->default('points')->after('reward_points'); // 'points', 'airtime', 'prize_draw'
        });

        // Profiles: Expanded demographic data
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('marital_status')->nullable()->after('income_band');
            $table->string('education')->nullable()->after('marital_status');
            $table->string('occupation')->nullable()->after('education');
            $table->text('food_preference')->nullable()->after('occupation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_password_set');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('reward_type');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['marital_status', 'education', 'occupation', 'food_preference']);
        });
    }
};
