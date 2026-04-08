<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Project;
use App\Services\ImageService;
use App\Support\UploadLimit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        $projects = \App\Models\Project::query()
            ->ordered()
            ->paginate(100);
        
        return view('admin.projects.index', compact('projects'));
    }
    
    /**
    * Show the form for creating a new resource.
    */
    public function create()
    {
        return view('admin.projects.create');
    }
    
    
    /**
    * Store a newly created resource in storage.
    */
    
    public function store(Request $request, ImageService $imageService)
    {
        $projectImageMaxKilobytes = UploadLimit::projectImageMaxKilobytes();
        $otherPhotosMaxBytes = UploadLimit::effectiveProjectOtherPhotosMaxBytes();
        $otherPhotosMaxLabel = UploadLimit::formatBytes($otherPhotosMaxBytes);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['required', 'image', 'max:'.$projectImageMaxKilobytes],
            'photos' => ['nullable', 'array', $this->otherPhotosTotalSizeRule($otherPhotosMaxBytes, $otherPhotosMaxLabel)],
            'photos.*' => ['image', 'max:'.$projectImageMaxKilobytes],
            'meta' => ['nullable', 'array'],
            'meta.credits' => ['nullable', 'array'],
            'meta.credits.*.role' => ['nullable', 'string', 'max:255'],
            'meta.credits.*.name' => ['nullable', 'string', 'max:255'],
        ]);
        
        
        // ⬇️ STESSO BLOCCO DI PRIMA
        $meta = $data['meta'] ?? null;
        
        if (is_array($meta) && isset($meta['credits'])) {
            $meta['credits'] = array_values(array_filter($meta['credits'], function ($c) {
                return !empty(trim($c['role'] ?? '')) || !empty(trim($c['name'] ?? ''));
            }));
            
            if (count($meta['credits']) === 0) {
                $meta = null;
            }
        }
        
        $data['meta'] = $meta;
        // ⬆️ FINE BLOCCO
        
        return DB::transaction(function () use ($request, $data, $imageService) {
            
            $slug = $this->makeSlug($data['title'] ?? null);
            
            // cover
            $coverPath = $imageService->storeProjectCover(
                $request->file('cover_image'),
                'projects/covers'
            );
            
            // crea project e SALVATI l’istanza
            $project = Project::create([
                'title' => $data['title'] ?? null,
                'place' => $data['place'] ?? null,
                'date' => $data['date'] ?? null,
                'slug' => $slug,
                'cover_image' => $coverPath,
                'sort_order' => ((int) Project::query()->max('sort_order')) + 1,
                'meta' => $data['meta'] ?? null,
                
            ]);
            
            // foto editoriali (multiple, opzionali)
            if ($request->hasFile('photos')) {
                $order = 0;
                
                foreach ($request->file('photos') as $file) {
                    $order++;
                    
                    $path = $imageService->storeProjectPhoto(
                        $file,
                        "projects/{$project->id}/photos"
                    );
                    
                    Photo::create([
                        'project_id' => $project->id,
                        'path' => $path,
                        'alt' => null,
                        'sort_order' => $order,
                    ]);
                }
            }
            
            return redirect()
            ->route('home')
            ->with('status', 'Progetto creato!');
        });
    }
    
    private function makeSlug(?string $title, ?Project $ignoreProject = null): string
    {
        $slug = Str::slug((string) $title);

        if ($slug === '') {
            $slug = 'project';
        }

        return $this->uniqueSlug($slug, $ignoreProject);
    }

    private function uniqueSlug(string $slug, ?Project $ignoreProject = null): string
    {
        $original = $slug;
        $i = 2;
        
        while (
            Project::query()
                ->when($ignoreProject, fn ($query) => $query->whereKeyNot($ignoreProject->getKey()))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $original . '-' . $i;
            $i++;
        }
        
        return $slug;
    }
    /**
    * Display the specified resource.
    */
    public function show(string $id)
    {
        //
    }
    
    /**
    * Show the form for editing the specified resource.
    */
    
    
    public function edit(Project $project)
    {
        $project->load('photos');
        
        return view('admin.projects.edit', compact('project'));
    }
    
    /**
    * Update the specified resource in storage.
    */
    
    public function update(Request $request, Project $project, ImageService $imageService)
    {
        $projectImageMaxKilobytes = UploadLimit::projectImageMaxKilobytes();
        $otherPhotosMaxBytes = UploadLimit::effectiveProjectOtherPhotosMaxBytes();
        $otherPhotosMaxLabel = UploadLimit::formatBytes($otherPhotosMaxBytes);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:'.$projectImageMaxKilobytes],
            'photos' => ['nullable', 'array', $this->otherPhotosTotalSizeRule($otherPhotosMaxBytes, $otherPhotosMaxLabel)],
            'photos.*' => ['image', 'max:'.$projectImageMaxKilobytes],
            'delete_photo_ids' => ['nullable', 'array'],
            'delete_photo_ids.*' => ['integer'],
            'meta' => ['nullable', 'array'],
            'meta.credits' => ['nullable', 'array'],
            'meta.credits.*.role' => ['nullable', 'string', 'max:255'],
            'meta.credits.*.name' => ['nullable', 'string', 'max:255'],
        ]);
        
        // normalizza meta (credits)
        $meta = $data['meta'] ?? null;
        
        if (is_array($meta) && isset($meta['credits'])) {
            $meta['credits'] = array_values(array_filter($meta['credits'], function ($c) {
                return trim((string)($c['role'] ?? '')) !== '' || trim((string)($c['name'] ?? '')) !== '';
            }));
            
            if (count($meta['credits']) === 0) {
                $meta = null;
            }
        }
        
        $data['meta'] = $meta;
        
        // cover
        if ($request->hasFile('cover_image')) {
            
            // opzionale: elimina vecchia cover
            if ($project->cover_image && Storage::disk('public')->exists($project->cover_image)) {
                Storage::disk('public')->delete($project->cover_image);
            }
            
            $data['cover_image'] = $imageService->storeProjectCover(
                $request->file('cover_image'),
                "projects/{$project->id}/covers"
            );
        } else {
            unset($data['cover_image']);
        }
        
        $nextTitle = $data['title'] ?? $project->title;

        if ($nextTitle !== $project->title || Str::startsWith($project->slug, 'project')) {
            $data['slug'] = $this->makeSlug($nextTitle, $project);
        }

        // ✅ aggiorna TUTTO
        $project->update([
            'title' => $nextTitle,
            'place' => $data['place'] ?? $project->place,
            'date' => $data['date'] ?? $project->date,
            'slug' => $data['slug'] ?? $project->slug,
            'cover_image' => $data['cover_image'] ?? $project->cover_image,
            'meta' => $data['meta'],
        ]);

        if ($request->hasFile('photos')) {
            $order = (int) ($project->photos()->max('sort_order') ?? 0);

            foreach ($request->file('photos') as $file) {
                $order++;
                $path = $imageService->storeProjectPhoto(
                    $file,
                    "projects/{$project->id}/photos"
                );

                Photo::create([
                    'project_id' => $project->id,
                    'path' => $path,
                    'alt' => null,
                    'sort_order' => $order,
                ]);
            }
        }

        $photosToDelete = $project->photos()
            ->whereIn('id', $data['delete_photo_ids'] ?? [])
            ->get();

        foreach ($photosToDelete as $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }

            $photo->delete();
        }

        return redirect()
            ->route('projects.show', ['project' => $project->slug])
            ->with('status', 'Progetto aggiornato!');
    }
    
    
    
    /**
    * Remove the specified resource from storage.
    */
    public function destroy(Project $project)
    {
        $project->delete();
        
        return redirect()
        ->route('home')
        ->with('status', 'Progetto eliminato!');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct', 'exists:projects,id'],
        ]);

        $orderedIds = array_values(array_map('intval', $data['ordered_ids']));
        $allProjectIds = Project::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
        $sortedOrderedIds = $orderedIds;

        sort($sortedOrderedIds);

        if ($sortedOrderedIds !== $allProjectIds) {
            return response()->json([
                'message' => 'La lista dei progetti inviata non e completa o non e valida.',
            ], 422);
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $position => $projectId) {
                Project::query()
                    ->whereKey($projectId)
                    ->update([
                        'sort_order' => $position + 1,
                    ]);
            }
        });

        return response()->json([
            'status' => 'ok',
        ]);
    }

    private function otherPhotosTotalSizeRule(int $maxBytes, string $maxLabel): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($maxBytes, $maxLabel): void {
            if (!is_array($value) || $value === [] || $maxBytes <= 0) {
                return;
            }

            $totalBytes = 0;

            foreach ($value as $file) {
                if ($file instanceof UploadedFile) {
                    $totalBytes += max(0, (int) $file->getSize());
                }
            }

            if ($totalBytes > $maxBytes) {
                $fail("The total size of other photos may not exceed {$maxLabel}.");
            }
        };
    }
}
