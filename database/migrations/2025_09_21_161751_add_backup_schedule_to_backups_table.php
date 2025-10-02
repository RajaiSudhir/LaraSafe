<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->enum('backup_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->time('backup_time')->default('02:00')->nullable();
            $table->dateTime('last_backup_at')->nullable();
            $table->dateTime('next_backup_at')->nullable();
            
            $table->index('backup_frequency');
            $table->index('next_backup_at');
            $table->index(['backup_frequency', 'next_backup_at'], 'idx_backup_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropIndex('idx_backup_schedule');
            $table->dropIndex(['backup_frequency']);
            $table->dropIndex(['next_backup_at']);
            $table->dropColumn(['backup_frequency', 'backup_time', 'last_backup_at', 'next_backup_at']);
        });
    }
};