<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfoPage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InfoPageController extends Controller
{
    public function edit()
    {
        $infoPage = InfoPage::query()->firstOrCreate(
            [],
            [
                'subtitle' => 'Stylist & Creative Consultant',
                'email' => 'sofia.brini@gmail.com',
                'instagram_url' => 'https://www.instagram.com/sofiabrini__/',
                'meta' => [
                    'credits' => [
                        [
                            'label' => 'developed by',
                            'name' => 'CRXTIAN HUB',
                            'url' => 'https://www.instagram.com/crxtianhub/',
                        ],
                        [
                            'label' => 'designed by',
                            'name' => 'FLIESNEVERLIE',
                            'url' => 'https://www.instagram.com/fliesneverlie/',
                        ],
                    ],
                    'sections' => [],
                ],
            ]
        );

        return view('admin.info.edit', compact('infoPage'));
    }

    public function update(Request $request, ImageService $imageService)
    {
        $infoPage = InfoPage::query()->firstOrCreate([]);

        $data = $request->validate([
            'subtitle' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'email' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'meta' => ['nullable', 'array'],
            'meta.sections' => ['nullable', 'array'],
            'meta.sections.*.title' => ['nullable', 'string', 'max:255'],
            'meta.sections.*.description' => ['nullable', 'string'],
        ]);

        $meta = $data['meta'] ?? [];

        $sections = array_values(array_filter($meta['sections'] ?? [], function ($section) {
            return trim((string) ($section['title'] ?? '')) !== ''
                || trim((string) ($section['description'] ?? '')) !== '';
        }));

        $meta = [
            'credits' => data_get($infoPage, 'meta.credits', []),
            'sections' => $sections,
        ];

        if ($request->hasFile('photo')) {
            if ($infoPage->photo_path && Storage::disk('public')->exists($infoPage->photo_path)) {
                Storage::disk('public')->delete($infoPage->photo_path);
            }

            $data['photo_path'] = $imageService->storeInfoPhoto(
                $request->file('photo'),
                'info'
            );
        }

        $infoPage->update([
            'subtitle' => $data['subtitle'] ?? null,
            'photo_path' => $data['photo_path'] ?? $infoPage->photo_path,
            'email' => $data['email'] ?? null,
            'instagram_url' => $data['instagram_url'] ?? null,
            'meta' => $meta,
        ]);

        return redirect()
            ->route('info')
            ->with('status', 'Info aggiornata!');
    }
}
