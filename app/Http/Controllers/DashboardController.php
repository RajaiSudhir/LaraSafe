<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Backup;
use App\Models\CreatedBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent backups
        $recentBackups = $this->getRecentBackups();
        
        // Get backup schedule (next 7 days)
        $upcomingBackups = $this->getUpcomingBackups();
        
        // Get project statistics
        $projectStats = $this->getProjectStats();
        
        // Get storage usage by project
        $storageUsage = $this->getStorageUsage();
        
        // Get backup success rate over time (last 30 days)
        $backupTrends = $this->getBackupTrends();

        return Inertia::render('Home', [
            'stats' => $stats,
            'recentBackups' => $recentBackups,
            'upcomingBackups' => $upcomingBackups,
            'projectStats' => $projectStats,
            'storageUsage' => $storageUsage,
            'backupTrends' => $backupTrends,
        ]);
    }

    private function getDashboardStats()
    {
        $totalProjects = Project::count();
        $totalBackups = CreatedBackup::count();
        $totalSize = CreatedBackup::sum('size');
        $successfulBackups = Backup::where('status', 'success')->count();
        $failedBackups = Backup::where('status', 'failed')->count();
        $pendingBackups = Backup::where('status', 'pending')->count();
        
        // Calculate success rate
        $totalBackupAttempts = $successfulBackups + $failedBackups + $pendingBackups;
        $successRate = $totalBackupAttempts > 0 ? round(($successfulBackups / $totalBackupAttempts) * 100, 1) : 0;
        
        // Today's backups
        $todayBackups = CreatedBackup::whereDate('created_at', today())->count();
        
        // This week's backups
        $weekBackups = CreatedBackup::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();

        return [
            'totalProjects' => $totalProjects,
            'totalBackups' => $totalBackups,
            'totalSize' => $totalSize,
            'successRate' => $successRate,
            'todayBackups' => $todayBackups,
            'weekBackups' => $weekBackups,
            'successfulBackups' => $successfulBackups,
            'failedBackups' => $failedBackups,
            'pendingBackups' => $pendingBackups,
        ];
    }

    private function getRecentBackups()
    {
        return CreatedBackup::with(['backup.project'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'project_name' => $backup->backup->project->name ?? 'Unknown',
                    'file_name' => $backup->file_name,
                    'size' => $backup->size,
                    'status' => $backup->backup->status ?? 'unknown',
                    'created_at' => $backup->created_at,
                    'expires_at' => $backup->expires_at,
                ];
            });
    }

    private function getUpcomingBackups()
    {
        return Backup::with('project')
            ->whereNotNull('next_backup_at')
            ->where('next_backup_at', '>=', now())
            ->where('next_backup_at', '<=', now()->addDays(7))
            ->orderBy('next_backup_at')
            ->limit(10)
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'project_name' => $backup->project->name,
                    'next_backup_at' => $backup->next_backup_at,
                    'frequency' => $backup->backup_frequency,
                    'backup_time' => $backup->backup_time,
                ];
            });
    }

    private function getProjectStats()
    {
        return Project::select('projects.*')
            ->withCount('backups')
            ->withSum('createdBackups as total_size', 'size')
            ->with(['createdBackups' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->limit(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'path' => $project->path,
                    'backups_count' => $project->backups_count,
                    'total_size' => $project->total_size ?? 0,
                    'last_backup' => $project->createdBackups->first()?->created_at,
                ];
            });
    }
    

    private function getStorageUsage()
    {
        return DB::table('created_backups')
            ->join('backups', 'created_backups.backup_id', '=', 'backups.id')
            ->join('projects', 'backups.project_id', '=', 'projects.id')
            ->select('projects.name as project_name', DB::raw('SUM(created_backups.size) as total_size'))
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('total_size')
            ->limit(5)
            ->get();
    }

    private function getBackupTrends()
    {
        $days = [];
        $successful = [];
        $failed = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->format('M j');
            
            $daySuccessful = Backup::where('status', 'success')
                ->whereDate('updated_at', $date)
                ->count();
            $dayFailed = Backup::where('status', 'failed')
                ->whereDate('updated_at', $date)
                ->count();
                
            $successful[] = $daySuccessful;
            $failed[] = $dayFailed;
        }
        
        return [
            'labels' => $days,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }
}