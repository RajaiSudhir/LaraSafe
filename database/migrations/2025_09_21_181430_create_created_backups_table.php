<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('created_backups', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID primary key
            $table->uuid('backup_id'); // UUID foreign key
            $table->string('file_name'); // actual zip filename
            $table->string('file_path')->nullable(); // Full path to the backup file
            $table->bigInteger('size')->nullable();
            $table->string('storage_disk')->default('local'); // Which disk the backup is stored on
            $table->string('checksum')->nullable(); // SHA256 checksum for integrity
            $table->timestamp('expires_at')->nullable(); // When backup expires
            $table->timestamps();
            
            $table->foreign('backup_id')->references('id')->on('backups')->onDelete('cascade');
            
            $table->index('backup_id');
            $table->index('storage_disk');
            $table->index('expires_at');
            $table->index(['backup_id', 'created_at'], 'idx_backup_created'); // For fetching backup history
            $table->index('checksum'); // For integrity checks
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('created_backups');
    }
};