<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    private const DEFAULT_QUALITY = 80;
    private const COVER_MAX_WIDTH = 1800;
    private const GALLERY_MAX_WIDTH = 1600;
    private const INFO_MAX_WIDTH = 1200;

    public function storeWebp(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 2000,
        int $quality = self::DEFAULT_QUALITY
    ): string {
        $manager = new ImageManager(new Driver());

        $image = $manager
            ->read($file)
            ->orient()
            ->scaleDown(width: $maxWidth)
            ->toWebp(quality: $quality);

        $filename = uniqid('', true).'.webp';
        $path = $directory.'/'.$filename;

        Storage::disk('public')->put($path, (string) $image);

        return $path;
    }

    public function storeProjectCover(UploadedFile $file, string $directory): string
    {
        return $this->storeWebp($file, $directory, self::COVER_MAX_WIDTH);
    }

    public function storeProjectPhoto(UploadedFile $file, string $directory): string
    {
        return $this->storeWebp($file, $directory, self::GALLERY_MAX_WIDTH);
    }

    public function storeInfoPhoto(UploadedFile $file, string $directory): string
    {
        return $this->storeWebp($file, $directory, self::INFO_MAX_WIDTH);
    }
}

//! DA usare nel controller poi successivamente!
// use App\Services\ImageService;

// public function store(Request $request, ImageService $imageService)
// {
//     $request->validate([
//         'image' => ['required', 'image', 'max:5120'],
//     ]);

//     $path = $imageService->storeWebp(
//         $request->file('image'),
//         'projects'
//     );

//     Photo::create([
//         'path' => $path,
//     ]);
// }
