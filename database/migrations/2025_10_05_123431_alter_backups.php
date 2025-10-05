<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->boolean('include_database')->default(false);
            $table->json('database_config')->nullable(); // Store DB configuration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('include_database');
            $table->dropColumn('database_config');
        });
    }
};
