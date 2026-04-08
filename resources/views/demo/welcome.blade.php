<x-layout title="Maria Sofia Brini — Fashion Stylist (Demo)" :barba="true" namespace="home">
    <x-slot:persistent>
        <x-navbar :show-info-link="true" :home-route="'demo.home'" :info-route="'demo.info'" />
    </x-slot:persistent>

    <section class="projectsGrid" aria-label="Projects">
        @forelse ($projects as $project)
            <div class="projectCard" data-project-id="{{ $project->id }}">
                <div class="projectCardMedia">
                    <a
                        href="{{ route('demo.projects.show', ['slug' => $project->slug]) }}"
                        class="projectCardLink"
                        data-project-key="{{ $project->slug }}"
                    >
                        @if ($project->cover_path)
                            <img
                                src="{{ asset($project->cover_path) }}"
                                alt="{{ $project->title ? $project->title . ' cover' : 'Project cover' }}"
                                class="projectCover"
                                data-project-key="{{ $project->slug }}"
                                loading="eager"
                                decoding="async"
                            >
                        @endif
                        <div class="projectCardOverlay">
                            <span class="projectCardTitle">{{ $project->title }}</span>
                        </div>
                    </a>
                </div>
            </div>
        @empty
            <p class="projectsEmpty">No projects yet.</p>
        @endforelse
    </section>

</x-layout>
