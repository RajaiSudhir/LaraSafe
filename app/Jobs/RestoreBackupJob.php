<?php

namespace App\Jobs;

use ZipArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CreatedBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $createdBackup;

    /**
     * Create a new job instance.
     */
    public function __construct(CreatedBackup $createdBackup)
    {
        $this->createdBackup = $createdBackup;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $filePath = storage_path('app/' . $this->createdBackup->file_path);

        $projectPathFromDb = $this->createdBackup->backup->project->path ?? null;

        if (!$projectPathFromDb) {
            $basePath = env('PROJECTS_BASE_PATH', base_path('projects'));
            $projectDirectory = $this->createdBackup->backup->project->directory ?? $this->createdBackup->backup->project->name;
            $projectPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $projectDirectory;
        } else {
            if (str_starts_with($projectPathFromDb, '~')) {
                $homeDir = getenv('HOME') ?: (function_exists('posix_getpwuid') ? posix_getpwuid(posix_getuid())['dir'] : null);
                if ($homeDir) {
                    $projectPath = $homeDir . DIRECTORY_SEPARATOR . ltrim($projectPathFromDb, '~/');
                } else {
                    $projectPath = $projectPathFromDb;
                }
            } elseif (str_starts_with($projectPathFromDb, '/')) {
                // Absolute path
                $projectPath = $projectPathFromDb;
            } else {
                // Relative path
                $basePath = env('PROJECTS_BASE_PATH', base_path('projects'));
                $projectPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $projectPathFromDb;
            }
        }

        Log::info("Restoring backup to path: $projectPath");

        if (!file_exists($filePath)) {
            Log::error("Backup file not found for restore", ['file_path' => $filePath]);
            return;
        }

        // Ensure target directory exists
        if (!file_exists($projectPath)) {
            mkdir($projectPath, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) === true) {
            // Extract files to the project directory, replacing existing files
            $zip->extractTo($projectPath);
            $zip->close();

            // Check if SQL dump file exists and import it
            $sqlFilePath = $projectPath . DIRECTORY_SEPARATOR . 'backup.sql';
            if (file_exists($sqlFilePath)) {
                try {
                    $sql = file_get_contents($sqlFilePath);
                    DB::unprepared($sql);
                    unlink($sqlFilePath); // Remove the SQL file after import
                } catch (Exception $e) {
                    Log::error("Database import failed during backup restore", ['error' => $e->getMessage()]);
                }
            }

            Log::info("Backup restored successfully for project", ['project' => $this->createdBackup->backup->project->id]);
        } else {
            Log::error("Failed to open backup zip file during restore", ['file_path' => $filePath]);
        }
    }
}
