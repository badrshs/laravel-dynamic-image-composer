<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('background_image');
            $table->integer('width')->default(2480);
            $table->integer('height')->default(3508);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->string('final_template_image')->nullable();
            $table->json('field_configuration')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_templates');
    }
};
