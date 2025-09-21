<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'file_name', 'storage_disk', 'size', 'status'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
