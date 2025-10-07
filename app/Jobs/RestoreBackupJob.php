<?php

namespace App\Jobs;

use App\Models\CreatedBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Exception;

class RestoreBackupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $createdBackup;

    public function __construct(CreatedBackup $createdBackup)
    {
        $this->createdBackup = $createdBackup;
    }

    public function handle()
    {
        $filePath = storage_path('app/' . $this->createdBackup->file_path);
        $projectPath = base_path('projects/' . $this->createdBackup->backup->project->directory); // Adjust according to your dir path

        if (!file_exists($filePath)) {
            \Log::error("Backup file not found for restore", ['file_path' => $filePath]);
            return;
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) === true) {
            // Extract all files to project directory
            $zip->extractTo($projectPath);
            $zip->close();

            // Check if SQL dump file exists in the extracted files and import DB
            $sqlFilePath = $projectPath . DIRECTORY_SEPARATOR . 'backup.sql';
            if (file_exists($sqlFilePath)) {
                try {
                    $sql = file_get_contents($sqlFilePath);
                    DB::unprepared($sql);
                    unlink($sqlFilePath); // Remove SQL file after import
                } catch (Exception $e) {
                    \Log::error("Database import failed during backup restore", ['error' => $e->getMessage()]);
                }
            }
            \Log::info("Backup restored successfully for project", ['project' => $this->createdBackup->backup->project->id]);
        } else {
            \Log::error("Failed to open backup zip file during restore", ['file_path' => $filePath]);
        }
    }
}
