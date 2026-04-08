<?php

namespace App\Http\Controllers;

use App\Models\InfoPage;
use App\Models\Project;

class PageController extends Controller
{
    public function home()
    {
        $projects = Project::with('photos')
            ->ordered()
            ->get();

        return view('welcome', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load('photos');

        return view('projects.show', compact('project'));
    }

    public function info()
    {
        $infoPage = InfoPage::query()->first();

        return view('info', compact('infoPage'));
    }
}
