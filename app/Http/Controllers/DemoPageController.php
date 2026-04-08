<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

class DemoPageController extends Controller
{
    public function home()
    {
        return view('demo.welcome', [
            'projects' => $this->projects(),
        ]);
    }

    public function show(string $slug)
    {
        $project = $this->projects()->firstWhere('slug', $slug);

        abort_unless($project, 404);

        return view('demo.projects.show', [
            'project' => $project,
        ]);
    }

    public function info()
    {
        return view('demo.info', [
            'infoPage' => $this->infoPage(),
        ]);
    }

    private function projects(): Collection
    {
        $projects = [
            [
                'id' => 901,
                'slug' => 'demo-editorial-alba',
                'title' => 'Editorial Alba',
                'place' => 'Milano',
                'date' => 'March 2026',
                'cover_path' => 'demo/cover-01.svg',
                'meta' => [
                    'credits' => [
                        ['role' => 'Styling', 'name' => 'Maria Sofia Brini'],
                        ['role' => 'Photographer', 'name' => 'Giulia Marchetti'],
                        ['role' => 'Make-up', 'name' => 'Elena Rossi'],
                    ],
                ],
                'photos' => [
                    ['path' => 'demo/gallery-01.svg', 'alt' => 'Editorial Alba photo 1'],
                    ['path' => 'demo/gallery-02.svg', 'alt' => 'Editorial Alba photo 2'],
                ],
            ],
            [
                'id' => 902,
                'slug' => 'demo-campaign-venice',
                'title' => 'Campaign Venice',
                'place' => 'Venezia',
                'date' => 'February 2026',
                'cover_path' => 'demo/cover-02.svg',
                'meta' => [
                    'credits' => [
                        ['role' => 'Styling', 'name' => 'Maria Sofia Brini'],
                        ['role' => 'Art Direction', 'name' => 'Studio Gamma'],
                        ['role' => 'Hair', 'name' => 'Marta Iori'],
                    ],
                ],
                'photos' => [
                    ['path' => 'demo/gallery-02.svg', 'alt' => 'Campaign Venice photo 1'],
                    ['path' => 'demo/gallery-03.svg', 'alt' => 'Campaign Venice photo 2'],
                ],
            ],
            [
                'id' => 903,
                'slug' => 'demo-runway-monaco',
                'title' => 'Runway Monaco',
                'place' => 'Monaco',
                'date' => 'January 2026',
                'cover_path' => 'demo/cover-03.svg',
                'meta' => [
                    'credits' => [
                        ['role' => 'Styling', 'name' => 'Maria Sofia Brini'],
                        ['role' => 'Production', 'name' => 'Atelier Nord'],
                        ['role' => 'Casting', 'name' => 'Luna Agency'],
                    ],
                ],
                'photos' => [
                    ['path' => 'demo/gallery-03.svg', 'alt' => 'Runway Monaco photo 1'],
                    ['path' => 'demo/gallery-01.svg', 'alt' => 'Runway Monaco photo 2'],
                ],
            ],
        ];

        return collect($projects)->map(function (array $project) {
            $photos = collect($project['photos'] ?? [])
                ->map(fn (array $photo) => (object) $photo);

            unset($project['photos']);

            return (object) array_merge($project, [
                'photos' => $photos,
            ]);
        });
    }

    private function infoPage(): object
    {
        return (object) [
            'subtitle' => 'Stylist & Creative Consultant',
            'email' => 'sofia.brini@gmail.com',
            'instagram_url' => 'https://www.instagram.com/sofiabrini__/',
            'photo_path' => 'demo/info-photo.svg',
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
                'sections' => [
                    [
                        'title' => 'About',
                        'description' => 'This is a demo info page for Vercel preview. Replace these texts with real biography content.',
                    ],
                    [
                        'title' => 'Services',
                        'description' => 'Editorial styling, campaign direction, runway consulting and creative production support.',
                    ],
                ],
            ],
        ];
    }
}
