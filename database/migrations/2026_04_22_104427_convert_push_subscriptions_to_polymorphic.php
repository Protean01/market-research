<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->unsignedBigInteger('subscribable_id')->after('id');
            $table->string('subscribable_type')->after('subscribable_id');
            $table->index(['subscribable_id', 'subscribable_type']);
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['subscribable_id', 'subscribable_type']);
            $table->dropColumn(['subscribable_id', 'subscribable_type']);

            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
