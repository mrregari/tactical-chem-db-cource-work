<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagents', function (Blueprint $table) {
            if (! Schema::hasColumn('reagents', 'fork_status')) $table->json('fork_status')->nullable();
            if (! Schema::hasColumn('reagents', 'also_in')) $table->json('also_in')->nullable();
            if (! Schema::hasColumn('reagents', 'recipe_data')) $table->json('recipe_data')->nullable();
            if (! Schema::hasColumn('reagents', 'extra_data')) $table->json('extra_data')->nullable();
        });

        Schema::table('reactions', function (Blueprint $table) {
            if (! Schema::hasColumn('reactions', 'fork_status')) $table->json('fork_status')->nullable();
            if (! Schema::hasColumn('reactions', 'fork_notes')) $table->json('fork_notes')->nullable();
            if (! Schema::hasColumn('reactions', 'also_in')) $table->json('also_in')->nullable();
            if (! Schema::hasColumn('reactions', 'extra_data')) $table->json('extra_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reagents', function (Blueprint $table) {
            $table->dropColumn(['fork_status', 'also_in', 'recipe_data', 'extra_data']);
        });
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropColumn(['fork_status', 'fork_notes', 'also_in', 'extra_data']);
        });
    }
};
