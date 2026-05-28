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
        Schema::table('responses', function (Blueprint $table) {
            $table->unsignedInteger('earned_points')->default(0)->after('answers');
            $table->json('enrichment_answers')->nullable()->after('answers');
            $table->timestamp('completed_at')->nullable()->after('enrichment_answers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn(['earned_points', 'enrichment_answers', 'completed_at']);
        });
    }
};
