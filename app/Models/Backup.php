<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Backup extends Model
{
    use HasFactory;

    // UUID Configuration
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'project_id', 'file_name', 'storage_disk', 'size', 'status', 
        'backup_frequency', 'backup_time', 'last_backup_at', 'next_backup_at'
    ];

    protected $casts = [
        'last_backup_at' => 'datetime',
        'next_backup_at' => 'datetime',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBackups()
    {
        return $this->hasMany(CreatedBackup::class);
    }
}