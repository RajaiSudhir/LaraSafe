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
            // combine today with selected time (default: now)
            $time = $request->time ?: now()->format('H:i');
            $todayWithTime = Carbon::parse($time);
    
            $nextBackup = match ($request->frequency) {
                'daily'   => $todayWithTime->copy()->addDay(),
                'weekly'  => $todayWithTime->copy()->addWeek(),
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
            'next_backup_at'   => $nextBackup,
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
        if ($backup->status === 'completed') {
            return back()->with('error', 'Cannot retry a completed backup.');
        }

        BackupProjectJob::dispatch($backup);

        Mail::to($backup->project->user->email ?? 'user@example.com')
            ->send(new BackupStatusMail($backup));

        return back()->with('status', 'Backup retry initiated successfully.');
    }

    public function download($id)
    {
        $backup = Backup::with('project')->findOrFail($id);
        logger($backup);
        // Make sure to include the .zip extension
        $filePath = storage_path("app/backups/{$backup->project->file_name}/{$backup->file_name}.zip");
        logger($filePath);
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        return response()->download($filePath, $backup->file_name . '.zip');
    }

    public function destroy($id)
    {
        $backup = Backup::with('project')->find($id);

        if (!$backup) {
            return redirect()
                ->route('manage-backups')
                ->with('error', 'Backup not found');
        }

        // Use the correct folder and file name from DB
        $disk = $backup->storage_disk ?? 'local';
        $zipPath = "backups/{$backup->project->file_name}/{$backup->file_name}.zip";

        if (Storage::disk($disk)->exists($zipPath)) {
            Storage::disk($disk)->delete($zipPath);
        }

        $backup->delete();

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
            // combine today with selected time (default: now)
            $time = $request->time ?: now()->format('H:i');
            $todayWithTime = Carbon::parse($time);
    
            $nextBackup = match ($request->frequency) {
                'daily'   => $todayWithTime->copy()->addDay(),
                'weekly'  => $todayWithTime->copy()->addWeek(),
                'monthly' => $todayWithTime->copy()->addMonth(),
            };
        }
        
        $backup->update([
            'project_id' => $validated['project_id'],
            'file_name' => $validated['file_name'],
            'storage_disk' => $validated['storage_disk'],
            'backup_frequency' => $validated['frequency'],
            'backup_time' => $validated['time'],
            'next_backup_at'   => $nextBackup,
        ]);

        BackupProjectJob::dispatch($backup);

        Mail::to($backup->project->user->email ?? 'user@example.com')
        ->send(new BackupStatusMail($backup));

        return redirect()
        ->route('manage-backups')
        ->with('success', 'Backup updated successfully');
    }
}
