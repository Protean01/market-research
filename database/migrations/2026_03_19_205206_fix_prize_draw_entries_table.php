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
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            if (Schema::hasColumn('prize_draw_entries', 'points_spent')) {
                $table->unsignedInteger('points_spent')->nullable()->change();
            }
            if (Schema::hasColumn('prize_draw_entries', 'draw_name')) {
                $table->string('draw_name')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            if (Schema::hasColumn('prize_draw_entries', 'points_spent')) {
                $table->unsignedInteger('points_spent')->nullable(false)->change();
            }
            if (Schema::hasColumn('prize_draw_entries', 'draw_name')) {
                $table->string('draw_name')->nullable(false)->change();
            }
        });
    }
};
