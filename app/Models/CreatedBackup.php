<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatedBackup extends Model
{
    protected $fillable = ['backup_id', 'file_name', 'size'];

    public function backup()
    {
        return $this->belongsTo(Backup::class);
    }
}
