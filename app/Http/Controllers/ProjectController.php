<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->paginate(20);
        return view('admin.projects.index', compact('projects'));
    }
    
    public function create()
    {
        return view('admin.projects.create');
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
            'place' => ['nullable','string','max:255'],
            'date' => ['nullable','string','max:255'],
            'cover_image' => ['required','image','max:5120'], // 5MB
            'meta' => ['nullable','array'],
        ]);
        
        $slugBase = $data['title'] ?: 'project';
        $slug = Str::slug($slugBase) ?: 'project';
        $slug = $this->uniqueSlug($slug);
        
        $coverPath = $request->file('cover_image')->store('projects/covers', 'public');
        
        $project = Project::create([
            'title' => $data['title'] ?? null,
            'place' => $data['place'] ?? null,
            'date' => $data['date'] ?? null,
            'slug' => $slug,
            'cover_image' => $coverPath,
            'meta' => $data['meta'] ?? null,
        ]);
        
        return redirect()->route('admin.projects.edit', $project)->with('status', 'Creato!');
    }
    
    public function edit(Project $project)
    {
        $project->load('photos');
        return view('admin.projects.edit', compact('project'));
    }
    
    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
            'place' => ['nullable','string','max:255'],
            'date' => ['nullable','string','max:255'],
            'cover_image' => ['nullable','image','max:5120'],
            'meta' => ['nullable','array'],
        ]);
        
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('projects/covers', 'public');
            $data['cover_image'] = $coverPath;
        }
        
        // slug: lo aggiornerei solo se vuoi; per ora lo lasciamo stabile.
        $project->update([
            'title' => $data['title'] ?? $project->title,
            'place' => $data['place'] ?? $project->place,
            'date' => $data['date'] ?? $project->date,
            'cover_image' => $data['cover_image'] ?? $project->cover_image,
            'meta' => $data['meta'] ?? $project->meta,
        ]);
        
        return back()->with('status', 'Salvato!');
    }
    
    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('status', 'Eliminato!');
    }
    
    private function uniqueSlug(string $slug): string
    {
        $original = $slug;
        $i = 2;
        
        while (Project::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i;
            $i++;
        }
        
        return $slug;
    }
}
