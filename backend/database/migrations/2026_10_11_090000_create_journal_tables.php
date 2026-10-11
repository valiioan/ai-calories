<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('target_weight_kg', 5, 2)->nullable()->after('fat_target_g');
        });

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fdc_id')->unique();
            $table->string('description');
            $table->string('data_type');
            $table->string('brand_owner')->nullable();
            $table->string('brand_name')->nullable();
            $table->decimal('serving_size', 8, 2)->nullable();
            $table->string('serving_unit')->nullable();
            $table->string('household_serving')->nullable();
            $table->decimal('gram_weight', 8, 2)->nullable();
            $table->decimal('energy_kcal', 8, 2)->nullable();
            $table->decimal('protein_g', 8, 2)->nullable();
            $table->decimal('carbohydrate_g', 8, 2)->nullable();
            $table->decimal('fat_g', 8, 2)->nullable();
            $table->decimal('fiber_g', 8, 2)->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();
        });

        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('eaten_at');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
            $table->string('note')->nullable();
            $table->text('original_text')->nullable();
            $table->text('assumptions')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'eaten_at']);
        });

        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->decimal('quantity', 8, 2);
            $table->string('unit');
            $table->decimal('grams', 8, 2)->nullable();
            $table->decimal('energy_kcal', 8, 2)->nullable();
            $table->decimal('protein_g', 8, 2)->nullable();
            $table->decimal('carbohydrate_g', 8, 2)->nullable();
            $table->decimal('fat_g', 8, 2)->nullable();
            $table->decimal('fiber_g', 8, 2)->nullable();
            $table->enum('nutrition_source', ['usda', 'manual']);
            $table->timestamps();
        });

        Schema::create('activity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('performed_at');
            $table->string('name');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->enum('intensity', ['low', 'moderate', 'high'])->nullable();
            $table->decimal('energy_kcal', 8, 2);
            $table->string('note')->nullable();
            $table->enum('source', ['manual', 'estimated']);
            $table->timestamps();
            $table->index(['user_id', 'performed_at']);
        });

        Schema::create('weight_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->decimal('weight_kg', 5, 2);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'recorded_on']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');
            $table->json('payload');
            $table->string('status');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->json('tool_calls')->nullable();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('revision_id')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name');
            $table->json('arguments');
            $table->json('result')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('journal_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_revisions');
        Schema::dropIfExists('agent_actions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('weight_entries');
        Schema::dropIfExists('activity_entries');
        Schema::dropIfExists('meal_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('foods');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('target_weight_kg');
        });
    }
};
