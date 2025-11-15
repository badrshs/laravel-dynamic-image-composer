<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('image_templates')->cascadeOnDelete();
            $table->string('name');
            $table->enum('element_type', ['image', 'text', 'shape'])->default('image');
            $table->string('image_path')->nullable();
            $table->integer('x_position')->default(0);
            $table->integer('y_position')->default(0);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('z_index')->default(0);
            $table->decimal('opacity', 3, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'is_active', 'z_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_elements');
    }
};
