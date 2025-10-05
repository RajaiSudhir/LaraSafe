<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Project;
use App\Services\ProjectBackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Jobs\BackupProjectJob;
use App\Mail\BackupStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\CreatedBackup;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(ProjectBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index()
    {
        $backups = Backup::with('project')->get();
        return Inertia::render('Backups/Backups', [
            'backups' => $backups,
        ]);
    }

    public function createBackup()
    {
        $projects = Project::all();
        return Inertia::render('Backups/CreateBackup', [
            'projects' => $projects,
        ]);
    }

    public function storeBackup(Request $request)
    {
        $rules = [
            'project_id'       => 'required|exists:projects,id',
            'file_name'        => 'required|string|max:255',
            'storage_disk'     => 'required|in:local,s3,other',
            'include_database' => 'boolean',
        ];
        $includeDatabase = (bool) $request->input('include_database');

        if ($includeDatabase) {
            $rules['db_source'] = 'required|in:env,custom,project_config';
            if ($request->db_source === 'custom') {
                $rules = array_merge($rules, [
                    'db_host'     => 'required|string',
                    'db_port'     => 'required|integer|between:1,65535',
                    'db_name'     => 'required|string',
                    'db_username' => 'required|string',
                    'db_password' => 'nullable|string',
                ]);
            }
            $rules['db_tables'] = 'required|in:all,selected';
            if ($request->db_tables === 'selected') {
                $rules['selected_tables'] = 'required|string';
            }
        }

        $request->validate($rules);

        // Calculate next backup time if scheduling
        $nextBackup = null;
        if ($request->frequency) {
            $time = $request->time ?: now()->format('H:i');
            $todayWithTime = Carbon::parse($time);
            $nextBackup = match ($request->frequency) {
                'daily'   => $todayWithTime->copy()->addDay(),
                'weekly'  => $todayWithTime->copy()->addWeek(),
                'monthly' => $todayWithTime->copy()->addMonth(),
            };
        }

        // Prepare database config payload
        $dbConfig = null;
        if ($includeDatabase) {
            $dbConfig = [
                'source' => $request->db_source,
                'tables' => $request->db_tables,
            ];
            if ($request->db_source === 'custom') {
                $dbConfig['credentials'] = encrypt([
                    'host'     => $request->db_host,
                    'port'     => $request->db_port,
                    'database' => $request->db_name,
                    'username' => $request->db_username,
                    'password' => $request->db_password,
                ]);
            }
            if ($request->db_tables === 'selected') {
                $dbConfig['selected_tables'] = array_map('trim', explode(',', $request->selected_tables));
            }
        }

        $backup = Backup::create([
            'project_id'           => $request->project_id,
            'file_name'            => $request->file_name,
            'storage_disk'         => $request->storage_disk,
            'status'               => 'pending',
            'frequency'            => $request->frequency,
            'time'                 => $request->time,
            'next_backup_at'       => $nextBackup,
            'include_database'     => $includeDatabase,
            'database_config'      => $dbConfig,
        ]);

        BackupProjectJob::dispatch($backup);

        Mail::to($backup->project->user->email ?? 'user@example.com')
            ->send(new \App\Mail\BackupStatusMail($backup));

        return back()->with('status', 'Backup created successfully!');
    }

    public function testDatabaseConnection(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_name'     => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host = $request->db_host === 'localhost' ? '127.0.0.1' : $request->db_host;
        $dsn  = "mysql:host={$host};port={$request->db_port};dbname={$request->db_name}";

        try {
            $pdo = new \PDO($dsn, $request->db_username, $request->db_password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            return response()->json(['success' => true, 'message' => 'Connection successful']);
        } catch (\PDOException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function run(Project $project)
    {
        $this->backupService->runBackup($project);
        return back()->with('status', 'Backup created successfully!');
    }

    public function retryBackup($id)
    {
        $backup = Backup::findOrFail($id);
        $backup->update(['status' => 'pending']);

        BackupProjectJob::dispatch($backup);

        return back()->with('status', 'Backup retry initiated successfully.');
    }

    /**
     * Download a specific backup file
     */
    public function download($id)
    {
        try {
            $createdBackup = CreatedBackup::with('backup.project')->findOrFail($id);

            // Use the file_path stored in the database
            $filePath = storage_path("app/{$createdBackup->file_path}");

            // Enhanced file existence check
            if (!file_exists($filePath)) {
                \Log::error("Backup file not found", [
                    'backup_id' => $id,
                    'expected_path' => $filePath,
                    'stored_path' => $createdBackup->file_path
                ]);

                return redirect()->back()->with('error', 'Backup file not found on server.');
            }

            // Optional: Verify file integrity if checksum exists
            if ($createdBackup->checksum) {
                $currentChecksum = hash_file('sha256', $filePath);
                if ($currentChecksum !== $createdBackup->checksum) {
                    \Log::warning("Backup file integrity check failed", [
                        'backup_id' => $id,
                        'expected_checksum' => $createdBackup->checksum,
                        'actual_checksum' => $currentChecksum
                    ]);

                    return redirect()->back()->with('error', 'Backup file may be corrupted. Please contact administrator.');
                }
            }

            // Create a user-friendly download name with timestamp
            $timestamp = $createdBackup->created_at->format('Y-m-d_H-i-s');
            $downloadName = "{$createdBackup->backup->project->name}_{$timestamp}.zip";

            // Log successful download for audit
            \Log::info("Backup downloaded", [
                'backup_id' => $id,
                'project' => $createdBackup->backup->project->name,
                'file_size' => $createdBackup->size,
                'download_name' => $downloadName
            ]);

            return response()->download($filePath, $downloadName);
        } catch (\Exception $e) {
            \Log::error("Download failed", [
                'backup_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to download backup. Please try again.');
        }
    }
    public function destroy($id)
    {
        $backup = Backup::with(['createdBackups', 'project'])->find($id);
        
        if (!$backup) {
            return redirect()->back()->with('error', 'Backup not found');
        }
    
        try {
            \DB::beginTransaction();
    
            $deletedFiles = 0;
            $failedFiles = 0;
            $freedSpace = 0;
            $backupName = $backup->file_name;
            $projectName = $backup->project->name;
    
            // Delete all created backup files for this backup
            foreach ($backup->createdBackups as $createdBackup) {
                try {
                    $freedSpace += $createdBackup->size ?? 0;
    
                    // Try to delete the file
                    if ($createdBackup->file_path) {
                        $fullPath = storage_path("app/{$createdBackup->file_path}");
                        
                        // Try Laravel Storage first
                        if (Storage::disk($createdBackup->storage_disk)->exists($createdBackup->file_path)) {
                            if (Storage::disk($createdBackup->storage_disk)->delete($createdBackup->file_path)) {
                                $deletedFiles++;
                                \Log::info("Deleted backup file: {$createdBackup->file_name}");
                            } else {
                                $failedFiles++;
                            }
                        } 
                        // Try direct file system deletion
                        elseif (file_exists($fullPath)) {
                            if (unlink($fullPath)) {
                                $deletedFiles++;
                                \Log::info("Direct deleted backup file: {$createdBackup->file_name}");
                            } else {
                                $failedFiles++;
                            }
                        } else {
                            // File doesn't exist, count as deleted
                            $deletedFiles++;
                        }
                    }
                } catch (\Exception $e) {
                    $failedFiles++;
                    \Log::error("Error deleting backup file: {$createdBackup->file_name}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
    
            // Delete the backup record (this will cascade delete created_backups)
            $backup->delete();
    
            \DB::commit();
    
            // Prepare success message
            $message = "Backup '{$backupName}' for project '{$projectName}' deleted successfully.";
            if ($deletedFiles > 0) {
                $message .= " Removed {$deletedFiles} backup files (" . $this->formatBytes($freedSpace) . " freed).";
            }
            if ($failedFiles > 0) {
                $message .= " Warning: {$failedFiles} files could not be deleted.";
            }
    
            return redirect()->back()->with('success', $message);
    
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error("Error deleting backup: {$backup->file_name}", [
                'backup_id' => $backup->id,
                'error' => $e->getMessage()
            ]);
    
            return redirect()->back()->with('error', 'Failed to delete backup. Please try again.');
        }
    }
    
    /**
     * Delete individual created backup file
     */
    public function destroyCreatedBackup($id)
    {
        $createdBackup = CreatedBackup::with('backup.project')->find($id);
        
        if (!$createdBackup) {
            return redirect()->back()->with('error', 'Backup file not found');
        }
    
        try {
            $fileName = $createdBackup->file_name;
            $size = $createdBackup->size ?? 0;
            $projectName = $createdBackup->backup->project->name;
    
            // Delete the actual file
            $fileDeleted = false;
            if ($createdBackup->file_path) {
                $fullPath = storage_path("app/{$createdBackup->file_path}");
                
                // Try Laravel Storage first
                if (Storage::disk($createdBackup->storage_disk)->exists($createdBackup->file_path)) {
                    $fileDeleted = Storage::disk($createdBackup->storage_disk)->delete($createdBackup->file_path);
                } 
                // Try direct file system deletion
                elseif (file_exists($fullPath)) {
                    $fileDeleted = unlink($fullPath);
                } else {
                    $fileDeleted = true; // File doesn't exist anyway
                }
            }
    
            // Delete the database record
            $createdBackup->delete();
    
            $message = "Backup file '{$fileName}' deleted successfully";
            if ($size > 0) {
                $message .= " (" . $this->formatBytes($size) . " freed)";
            }
            if (!$fileDeleted) {
                $message .= ". Warning: Physical file could not be removed.";
            }
    
            return redirect()->back()->with('success', $message);
    
        } catch (\Exception $e) {
            \Log::error("Error deleting created backup", [
                'created_backup_id' => $createdBackup->id,
                'error' => $e->getMessage()
            ]);
    
            return redirect()->back()->with('error', 'Failed to delete backup file.');
        }
    }
    
    private function formatBytes($size, $precision = 2): string
    {
        if ($size === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

    public function edit($id)
    {
        $backup = Backup::with('project')->find($id);
        $projects = Project::all();
        return Inertia::render('Backups/EditBackups', [
            'backup' => $backup,
            'projects' => $projects,
        ]);
    }

    public function updateBackup(Request $request, $id)
    {
        $backup = Backup::findOrFail($id);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'file_name' => 'required|string|max:255',
            'storage_disk' => 'required|in:local,s3,other',
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'time' => 'nullable|date_format:H:i',
        ]);

        $nextBackup = null;
        if ($request->frequency) {
            $time = $request->time ?: now()->format('H:i');
            $todayWithTime = Carbon::parse($time);

            $nextBackup = match ($request->frequency) {
                'daily' => $todayWithTime->copy()->addDay(),
                'weekly' => $todayWithTime->copy()->addWeek(),
                'monthly' => $todayWithTime->copy()->addMonth(),
            };
        }

        $backup->update([
            'project_id' => $validated['project_id'],
            'file_name' => $validated['file_name'],
            'storage_disk' => $validated['storage_disk'],
            'backup_frequency' => $validated['frequency'],
            'backup_time' => $validated['time'],
            'next_backup_at' => $nextBackup,
        ]);

        BackupProjectJob::dispatch($backup);

        Mail::to($backup->project->user->email ?? 'user@example.com')
            ->send(new BackupStatusMail($backup));

        return redirect()
            ->route('manage-backups')
            ->with('success', 'Backup updated successfully');
    }

    /**
     * View all backups for a specific backup configuration
     */
    public function viewBackups($id)
    {
        $backups = CreatedBackup::with('backup.project')
            ->where('backup_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Backups/View-Backups', [
            'backups' => $backups,
        ]);
    }

    /**
     * Clean up expired backups
     */
    public function cleanupExpiredBackups()
    {
        $expiredBackups = CreatedBackup::where('expires_at', '<', now())->get();

        foreach ($expiredBackups as $backup) {
            // Delete physical file
            $disk = $backup->storage_disk ?? 'local';
            if (Storage::disk($disk)->exists($backup->file_path)) {
                Storage::disk($disk)->delete($backup->file_path);
            }

            // Delete database record
            $backup->delete();
        }

        return response()->json([
            'message' => "Cleaned up {$expiredBackups->count()} expired backups"
        ]);
    }

    public function destroySubBackup($id)
    {
        $createdBackup = CreatedBackup::with(['backup.project'])->findOrFail($id);
        
        try {
            $fileName = $createdBackup->file_name;
            $size = $createdBackup->size ?? 0;
            $projectName = $createdBackup->backup->project->name ?? 'Unknown Project';
    
            // Delete the actual file from storage
            $fileDeleted = false;
            $filePath = $createdBackup->file_path;
    
            if ($filePath) {
                $storageDisk = $createdBackup->storage_disk ?? 'local';
                
                // Try Laravel Storage first
                if (Storage::disk($storageDisk)->exists($filePath)) {
                    $fileDeleted = Storage::disk($storageDisk)->delete($filePath);
                    \Log::info("Deleted backup file via Storage facade", [
                        'file_path' => $filePath,
                        'storage_disk' => $storageDisk
                    ]);
                } 
                // Try direct file system deletion as fallback
                else {
                    $fullPath = storage_path("app/{$filePath}");
                    if (file_exists($fullPath)) {
                        $fileDeleted = unlink($fullPath);
                        \Log::info("Deleted backup file via direct filesystem", [
                            'full_path' => $fullPath
                        ]);
                    } else {
                        // File doesn't exist, consider it "deleted"
                        $fileDeleted = true;
                        \Log::info("Backup file already missing", [
                            'file_path' => $filePath
                        ]);
                    }
                }
            }
    
            // Delete the database record
            $createdBackup->delete();
    
            // Prepare success message
            $message = "Backup '{$fileName}' deleted successfully";
            if ($size > 0) {
                $message .= " (" . $this->formatBytes($size) . " freed)";
            }
            if (!$fileDeleted) {
                $message .= ". Warning: Physical file could not be removed from storage.";
            }
    
            // Check if this is an AJAX/JSON request (from Vue.js)
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
    
            return redirect()->back()->with('success', $message);
    
        } catch (\Exception $e) {
            \Log::error("Error deleting created backup", [
                'created_backup_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            $errorMessage = 'Failed to delete backup: ' . $e->getMessage();
    
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
    
            return redirect()->back()->with('error', $errorMessage);
        }
    }
}
