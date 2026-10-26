<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reagent_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_loadout_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'reagent_id']);
            $table->unique(['user_id', 'equipment_loadout_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
