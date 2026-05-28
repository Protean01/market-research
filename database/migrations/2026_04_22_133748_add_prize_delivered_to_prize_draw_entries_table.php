<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->boolean('prize_delivered')->default(false)->after('won_at');
            $table->timestamp('delivered_at')->nullable()->after('prize_delivered');
        });
    }

    public function down(): void
    {
        Schema::table('prize_draw_entries', function (Blueprint $table) {
            $table->dropColumn(['prize_delivered', 'delivered_at']);
        });
    }
};
