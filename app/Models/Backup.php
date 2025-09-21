<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CreatedBackup;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'file_name', 'storage_disk', 'size', 'status', 'backup_frequency',
    'backup_time',
    'last_backup_at',
    'next_backup_at',];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBackups()
    {
        return $this->hasMany(CreatedBackup::class);
    }

}
