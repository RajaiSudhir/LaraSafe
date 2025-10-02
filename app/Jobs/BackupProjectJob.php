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
use Exception;
use Carbon\Carbon;

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
        $project = $this->backup->project;
        $sourceDir = rtrim($project->path, '/');
        $baseName = pathinfo($this->backup->file_name, PATHINFO_FILENAME);
        $disk = $this->backup->storage_disk ?? 'local';

        // Create folder inside private storage for this project's backups
        $backupFolder = "private/backups/{$project->name}";
        Storage::disk($disk)->makeDirectory($backupFolder);

        // Use timestamp to make unique filename
        $timestamp = now()->format('Y_m_d_H_i_s');
        $fileName = $baseName . '_' . $timestamp . '.zip';
        
        // Store relative path in database for security
        $relativePath = "{$backupFolder}/{$fileName}";
        $fullPath = storage_path("app/{$relativePath}");

        // Make sure folder exists
        $dirPath = dirname($fullPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($fullPath, ZipArchive::CREATE);

        if ($openResult === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getRealPath();
                    $relativePath_internal = substr($filePath, strlen($sourceDir) + 1);
                    $zip->addFile($filePath, $relativePath_internal);
                }
            }

            $zip->close();

            // Generate checksum for integrity verification
            $checksum = hash_file('sha256', $fullPath);

            // Save in created_backups with correct file path
            $createdBackup = $this->backup->createdBackups()->create([
                'file_name' => pathinfo($fileName, PATHINFO_FILENAME),
                'file_path' => $relativePath, // Store relative path from storage/app/
                'size' => filesize($fullPath),
                'storage_disk' => $disk,
                'checksum' => $checksum,
                'expires_at' => now()->addDays($this->backup->retention_days ?? 30),
            ]);

            // Update main backup status with reference to latest backup
            $this->backup->update([
                'status' => 'success',
                'size' => filesize($fullPath),
                'last_created_backup_id' => $createdBackup->id,
                'last_backup_at' => now(),
            ]);

            Log::info('Backup created successfully', [
                'backup_id' => $this->backup->id,
                'file_path' => $relativePath,
                'size' => filesize($fullPath)
            ]);

            try {
                Mail::to($this->backup->project->user->email ?? 'user@example.com')
                    ->send(new BackupStatusMail($this->backup, $createdBackup));
            } catch (Exception $e) {
                Log::error('Failed to send backup status email', [
                    'backup_id' => $this->backup->id,
                    'error' => $e->getMessage(),
                ]);
            }

        } else {
            Log::error('ZipArchive failed to open', [
                'fullPath' => $fullPath,
                'code' => $openResult,
            ]);

            $this->backup->update([
                'status' => 'failed',
                'error_message' => 'Unable to create zip file',
                'last_backup_at' => now(),
            ]);

            try {
                Mail::to($this->backup->project->user->email ?? 'user@example.com')
                    ->send(new BackupStatusMail($this->backup));
            } catch (Exception $e) {
                Log::error('Failed to send backup status email', [
                    'backup_id' => $this->backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}