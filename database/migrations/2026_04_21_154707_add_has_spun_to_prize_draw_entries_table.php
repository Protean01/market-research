<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->boolean('has_spun')->default(false)->after('points_entered');
        });
    }

    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->dropColumn('has_spun');
        });
    }
};
