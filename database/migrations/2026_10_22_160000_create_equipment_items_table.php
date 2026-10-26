<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_fork', 80)->index();
            $table->string('source_name')->nullable();
            $table->string('prototype_id', 255);
            $table->string('name', 255)->index();
            $table->text('description')->nullable();
            $table->string('category', 50)->default('equipment')->index();
            $table->string('prototype_type', 50)->default('entity');
            $table->json('parent_ids')->nullable();
            $table->string('sprite_path', 500)->nullable();
            $table->json('slots')->nullable();
            $table->json('tags')->nullable();
            $table->json('components')->nullable();
            $table->string('source_file', 500)->nullable();
            $table->text('source_url')->nullable();
            $table->timestamps();
            $table->unique(['source_fork', 'prototype_id'], 'equipment_source_proto_unique');
            $table->index(['source_fork', 'category', 'name'], 'equipment_source_category_name_idx');
        });

        foreach (['Броня', 'Оружие', 'Медик', 'Инженер', 'Разведка', 'Общий комплект'] as $name) {
            if (! DB::table('categories')->where('type', 'equipment')->where('name', $name)->exists()) {
                DB::table('categories')->insert(['name' => $name, 'type' => 'equipment', 'description' => 'Категория билдов экипировки', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::table('loadout_items', function (Blueprint $table) {
            $table->foreignId('equipment_item_id')->nullable()->after('equipment_loadout_id')->constrained('equipment_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['Броня', 'Оружие', 'Медик', 'Инженер', 'Разведка', 'Общий комплект'] as $name) {
            if (! DB::table('categories')->where('type', 'equipment')->where('name', $name)->exists()) {
                DB::table('categories')->insert(['name' => $name, 'type' => 'equipment', 'description' => 'Категория билдов экипировки', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::table('loadout_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_item_id');
        });
        Schema::dropIfExists('equipment_items');
    }
};
