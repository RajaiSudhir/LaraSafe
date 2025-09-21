<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    public function index()
    {
        $projects = Project::all();
        return Inertia::render('Projects/Projects', [
            'projects' => $projects
        ]);
    }

    public function createProject()
    {
        return Inertia::render('Projects/CreateProject');
    }

    public function storeProject(Request $request)
    {
        $validation = $request->validate([
            'name' => 'required',
            'description' => 'nullable|string',
            'path' => 'required|string',
        ]);

        $project = Project::create($validation);

        return redirect()->route('manage-projects')->with('success', 'Project created successfully');

    }

    public function destroyProject($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return redirect()->route('manage-projects')->with('error', 'Project not found');
        }
        $project->delete();
        return redirect()->route('manage-projects')->with('success', 'Project deleted successfully');
    }
}
