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
            $table->enum('backup_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->time('backup_time')->default('02:00')->nullable();
            $table->dateTime('last_backup_at')->nullable();
            $table->dateTime('next_backup_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('backup_frequency');
            $table->dropColumn('backup_time');
            $table->dropColumn('last_backup_at');
            $table->dropColumn('next_backup_at');
        });
    }
};
