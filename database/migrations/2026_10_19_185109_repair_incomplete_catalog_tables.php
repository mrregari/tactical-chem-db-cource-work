<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('categories');
        Schema::table('categories', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }
            if (! in_array('type', $columns, true)) {
                $table->enum('type', ['reagent', 'equipment']);
            }
            if (! in_array('description', $columns, true)) {
                $table->text('description')->nullable();
            }
        });

        $columns = Schema::getColumnListing('reagents');
        Schema::table('reagents', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }
            if (! in_array('description', $columns, true)) {
                $table->text('description')->nullable();
            }
            if (! in_array('color', $columns, true)) {
                $table->string('color')->nullable();
            }
            if (! in_array('effects', $columns, true)) {
                $table->text('effects')->nullable();
            }
            if (! in_array('category_id', $columns, true)) {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('user_id', $columns, true)) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('status', $columns, true)) {
                $table->enum('status', ['draft', 'published', 'rejected'])->default('draft');
            }
        });

        $columns = Schema::getColumnListing('reactions');
        Schema::table('reactions', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }
            if (! in_array('description', $columns, true)) {
                $table->text('description')->nullable();
            }
            if (! in_array('ingredients', $columns, true)) {
                $table->json('ingredients');
            }
            if (! in_array('result_reagent_id', $columns, true)) {
                $table->foreignId('result_reagent_id')->constrained('reagents')->cascadeOnDelete();
            }
            if (! in_array('user_id', $columns, true)) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('status', $columns, true)) {
                $table->enum('status', ['draft', 'published', 'rejected'])->default('draft');
            }
        });

        $columns = Schema::getColumnListing('equipment_loadouts');
        Schema::table('equipment_loadouts', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }
            if (! in_array('description', $columns, true)) {
                $table->text('description')->nullable();
            }
            if (! in_array('role', $columns, true)) {
                $table->string('role');
            }
            if (! in_array('category_id', $columns, true)) {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('user_id', $columns, true)) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('status', $columns, true)) {
                $table->enum('status', ['draft', 'published', 'rejected'])->default('draft');
            }
        });

        $columns = Schema::getColumnListing('loadout_items');
        Schema::table('loadout_items', function (Blueprint $table) use ($columns) {
            if (! in_array('equipment_loadout_id', $columns, true)) {
                $table->foreignId('equipment_loadout_id')->constrained()->cascadeOnDelete();
            }
            if (! in_array('item_name', $columns, true)) {
                $table->string('item_name');
            }
            if (! in_array('slot', $columns, true)) {
                $table->string('slot');
            }
            if (! in_array('quantity', $columns, true)) {
                $table->integer('quantity')->default(1);
            }
            if (! in_array('notes', $columns, true)) {
                $table->text('notes')->nullable();
            }
        });

        $columns = Schema::getColumnListing('comments');
        Schema::table('comments', function (Blueprint $table) use ($columns) {
            if (! in_array('content', $columns, true)) {
                $table->text('content');
            }
            if (! in_array('commentable_type', $columns, true) && ! in_array('commentable_id', $columns, true)) {
                $table->morphs('commentable');
            }
            if (! in_array('user_id', $columns, true)) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Existing installations may have had these columns before this repair.
    }
};
