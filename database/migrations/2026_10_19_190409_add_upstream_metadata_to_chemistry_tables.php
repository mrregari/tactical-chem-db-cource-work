<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagents', function (Blueprint $table) {
            $table->string('upstream_id')->nullable()->unique();
            $table->string('source_fork')->nullable();
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->string('upstream_id')->nullable()->unique();
            $table->string('source_fork')->nullable();
            $table->decimal('product_amount', 12, 4)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropUnique(['upstream_id']);
            $table->dropColumn(['upstream_id', 'source_fork', 'product_amount']);
        });
        Schema::table('reagents', function (Blueprint $table) {
            $table->dropUnique(['upstream_id']);
            $table->dropColumn(['upstream_id', 'source_fork']);
        });
    }
};
