<?php

namespace App\Jobs;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Mail\BackupStatusMail;
use Illuminate\Support\Facades\Mail;

class BackupProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $backup;

    public function __construct(Backup $backup)
    {
        $this->backup = $backup;
    }

    public function handle(): void
    {
        $project   = $this->backup->project;
        $sourceDir = rtrim($project->path, '/');
        $baseName  = pathinfo($this->backup->file_name, PATHINFO_FILENAME);
        $disk      = $this->backup->storage_disk ?? 'local';
    
        // Create folder inside storage for this project's backups
        $backupFolder = "backups/{$project->file_name}";
        Storage::disk($disk)->makeDirectory($backupFolder);
    
        // Use timestamp to make unique filename
        $timestamp = now()->format('Y_m_d_H_i_s');
        $fileName  = $baseName . '_' . $timestamp . '.zip';
        $zipPath   = storage_path("app/{$backupFolder}/{$fileName}");
    
        // Make sure folder exists
        $dirPath = dirname($zipPath);
        if (!is_dir($dirPath)) mkdir($dirPath, 0755, true);
    
        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath, ZipArchive::CREATE);
    
        if ($openResult === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
    
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filePath     = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
    
            $zip->close();
    
            // Save in created_backups
            $createdBackup = $this->backup->createdBackups()->create([
                'file_name' => pathinfo($fileName, PATHINFO_FILENAME),
                'size'      => filesize($zipPath),
            ]);
    
            // Update main backup status
            $this->backup->update([
                'status' => 'success',
                'size'   => filesize($zipPath),
            ]);
    
            Mail::to($this->backup->project->user->email ?? 'user@example.com')
                ->send(new BackupStatusMail($this->backup, $createdBackup));
    
        } else {
            Log::error('ZipArchive failed to open', [
                'zipPath' => $zipPath,
                'code'    => $openResult,
            ]);
    
            $this->backup->update([
                'status'        => 'failed',
                'error_message' => 'Unable to create zip file',
            ]);
    
            Mail::to($this->backup->project->user->email ?? 'user@example.com')
                ->send(new BackupStatusMail($this->backup));
        }
    }
       
}
