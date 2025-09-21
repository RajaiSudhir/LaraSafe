<?php

namespace App\Services;

use App\Models\Backup;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use ZipArchive;
use Storage;

class ProjectBackupService
{
    public function runBackup($project)
    {
        $disk = 'backups'; // your disk in filesystems.php

        // build filename
        $fileName = $project->name . '_' . now()->format('Y-m-d_H-i-s') . '.zip';

        // ensure directory exists
        Storage::disk($disk)->makeDirectory('/');

        // Create a zip archive manually for simplicity
        $zipPath = Storage::disk($disk)->path($fileName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $project->path);
            $zip->close();

            // store backup info in DB
            Backup::create([
                'project_id'  => $project->id,
                'file_name'   => $fileName,
                'storage_disk'=> $disk,
                'size'        => Storage::disk($disk)->size($fileName),
                'status'      => 'success',
            ]);

            return true;
        } else {
            // failed backup
            Backup::create([
                'project_id'  => $project->id,
                'file_name'   => $fileName,
                'storage_disk'=> $disk,
                'status'      => 'failed',
            ]);
            return false;
        }
    }

    private function addFilesToZip($zip, $folderPath, $parentFolder = '')
    {
        $files = scandir($folderPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $folderPath . '/' . $file;
            $localPath = $parentFolder ? $parentFolder . '/' . $file : $file;

            if (is_dir($fullPath)) {
                $this->addFilesToZip($zip, $fullPath, $localPath);
            } else {
                $zip->addFile($fullPath, $localPath);
            }
        }
    }
}
