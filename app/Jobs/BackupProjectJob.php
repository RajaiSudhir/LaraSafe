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
        $fileName  = pathinfo($this->backup->file_name, PATHINFO_FILENAME) . '.zip';
        $disk      = $this->backup->storage_disk ?? 'local';

        // Create folder inside storage for this project's backups
        $backupFolder = "backups/{$project->file_name}";
        Storage::disk($disk)->makeDirectory($backupFolder);

        // Absolute path for zip (always inside local storage_path)
        $zipPath = storage_path("app/{$backupFolder}/{$fileName}");

        // Make sure the folder actually exists
        $dirPath = dirname($zipPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

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

            $this->backup->update([
                'status' => 'success',
                'size'   => filesize($zipPath),
            ]);

            Mail::to($this->backup->project->user->email ?? 'user@example.com')
            ->send(new BackupStatusMail($this->backup));
        } else {
            Log::error('ZipArchive failed to open', [
                'zipPath' => $zipPath,
                'code'    => $openResult,
            ]);

            Mail::to($this->backup->project->user->email ?? 'user@example.com')
            ->send(new BackupStatusMail($this->backup));

            $this->backup->update([
                'status'        => 'failed',
                'error_message' => 'Unable to create zip file',
            ]);
        }
    }
}
