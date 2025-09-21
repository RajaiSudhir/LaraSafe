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

        $backup = Backup::create([
            'project_id' => $request->project_id,
            'file_name' => $request->file_name,
            'storage_disk' => $request->storage_disk,
            'status' => 'pending',
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
        $backup = Backup::find($id);
        if (!$backup) {
            return redirect()->route('backups/manage-backups')->with('error', 'Backup not found');
        }
        $backup->delete();
        return redirect()->route('backups/manage-backups')->with('success', 'Backup deleted successfully');
    }
}
