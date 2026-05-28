<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_templates', function (Blueprint $table) {
            $table->string('name')->after('client_id')->nullable();
            $table->json('questions')->after('name')->nullable();

            // We will drop these once we migrate existing data or if starting fresh
            $table->dropColumn(['text', 'type', 'options']);
        });
    }

    public function down(): void
    {
        Schema::table('question_templates', function (Blueprint $table) {
            $table->string('text')->nullable();
            $table->string('type')->nullable();
            $table->json('options')->nullable();
            $table->dropColumn(['name', 'questions']);
        });
    }
};
