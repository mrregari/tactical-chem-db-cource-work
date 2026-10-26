<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['guest', 'user', 'admin'])->default('user')->after('email');
            });
        }
    }

    public function down(): void
    {
        // The column may predate this repair; a rollback must not remove it.
    }
};
