<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->enum('sex', ['female', 'male', 'other', 'unspecified'])->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->enum('activity_level', ['sedentary', 'light', 'moderate', 'active', 'very_active'])->nullable();
            $table->enum('goal', ['lose', 'maintain', 'gain'])->default('maintain');
            $table->unsignedInteger('calorie_target')->nullable();
            $table->decimal('protein_target_g', 6, 1)->nullable();
            $table->decimal('carbohydrate_target_g', 6, 1)->nullable();
            $table->decimal('fat_target_g', 6, 1)->nullable();
            $table->string('timezone')->default('Europe/Bucharest');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
