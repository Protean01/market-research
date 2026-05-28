<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->string('type');
            $table->json('options')->nullable();
            $table->string('category')->default('general');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_templates');
    }
};
