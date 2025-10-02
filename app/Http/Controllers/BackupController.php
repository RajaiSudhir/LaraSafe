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
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'file_name' => 'required|string|max:255',
            'storage_disk' => 'required|in:local,s3,other',
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

        $backup = Backup::create([
            'project_id' => $request->project_id,
            'file_name' => $request->file_name,
            'storage_disk' => $request->storage_disk,
            'status' => 'pending',
            'frequency' => $request->frequency,
            'time' => $request->time,
            'next_backup_at' => $nextBackup,
        ]);

        BackupProjectJob::dispatch($backup);

        Mail::to($backup->project->user->email ?? 'user@example.com')
            ->send(new BackupStatusMail($backup));

        return back()->with('status', 'Backup created successfully!');
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
        $createdBackup = CreatedBackup::with('backup.project')->findOrFail($id);

        $disk = $createdBackup->storage_disk ?? 'local';
        if (Storage::disk($disk)->exists($createdBackup->file_path)) {
            Storage::disk($disk)->delete($createdBackup->file_path);
        }

        // Delete the database record
        $createdBackup->delete();

        return redirect()
            ->route('manage-backups')
            ->with('success', 'Backup deleted successfully');
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
}
