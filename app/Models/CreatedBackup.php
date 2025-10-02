<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreatedBackup extends Model
{
    // UUID Configuration
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'backup_id', 'file_name', 'file_path', 'size',
        'storage_disk', 'checksum', 'expires_at',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    /**
     * Verify backup file integrity using checksum
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->checksum) {
            return false; // Can't verify without checksum
        }

        if (!Storage::disk($this->storage_disk)->exists($this->file_path)) {
            return false; // File doesn't exist
        }

        $filePath = Storage::disk($this->storage_disk)->path($this->file_path);
        $currentChecksum = hash_file('sha256', $filePath);
        
        return $currentChecksum === $this->checksum;
    }

    /**
     * Check if backup file exists on disk
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->file_path);
    }

    /**
     * Get human readable file size
     */
    protected function fileSizeHuman(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->formatBytes($this->size),
        );
    }

    /**
     * Check if backup has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Delete the backup file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk($this->storage_disk)->delete($this->file_path);
        }
        return true;
    }

    private function formatBytes($size, $precision = 2): string
    {
        if ($size === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

}