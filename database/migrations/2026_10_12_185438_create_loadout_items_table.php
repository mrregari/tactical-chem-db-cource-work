<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_loadout_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->string('slot')->comment('Слот: helmet, armor, weapon и т.д.');
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadout_items');
    }
};
