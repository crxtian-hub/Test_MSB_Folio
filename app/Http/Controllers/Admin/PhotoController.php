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
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request, Project $project, ImageService $imageService)
    {
        $projectImageMaxKilobytes = UploadLimit::projectImageMaxKilobytes();
        $otherPhotosMaxBytes = UploadLimit::effectiveProjectOtherPhotosMaxBytes();
        $otherPhotosMaxLabel = UploadLimit::formatBytes($otherPhotosMaxBytes);

        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', $this->otherPhotosTotalSizeRule($otherPhotosMaxBytes, $otherPhotosMaxLabel)],
            'photos.*' => ['image', 'max:'.$projectImageMaxKilobytes],
        ]);
        
        // sort_order: append in coda
        $startOrder = (int) $project->photos()->max('sort_order');
        $order = $startOrder;
        
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
        
        return back()->with('status', 'Foto caricate!');
    }
    
    public function destroy(Photo $photo)
    {
        // cancella file fisico
        if ($photo->path && Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }
        
        $photo->delete();
        
        return back()->with('status', 'Foto eliminata!');
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
