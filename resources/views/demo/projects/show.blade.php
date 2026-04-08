<x-layout :title="($project->title ?: 'Project') . ' — Maria Sofia Brini (Demo)'" :barba="true" namespace="project">
    <x-slot:persistent>
        <x-navbar :title="'MARIA SOFIA BRINI'" :show-info-link="true" :home-route="'demo.home'" :info-route="'demo.info'" />
    </x-slot:persistent>

    <section class="projectShow">
        <a href="{{ route('demo.home') }}" class="projectShowClose" aria-label="Close project">
            <ion-icon name="close-sharp" aria-hidden="true"></ion-icon>
        </a>

        <div class="projectShowMain">
            @if ($project->cover_path)
                <img
                    src="{{ asset($project->cover_path) }}"
                    alt="{{ $project->title ? $project->title . ' cover' : 'Project cover' }}"
                    class="projectShowCover"
                    data-project-key="{{ $project->slug }}"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            @endif
            @if ($project->place || $project->date)
                <div class="projectShowInfoUnderCover">
                    @if ($project->place)
                        <p class="projectShowText">{{ $project->place }}</p>
                    @endif

                    @if ($project->date)
                        <p class="projectShowText">{{ $project->date }}</p>
                    @endif
                </div>
            @endif
        </div>

        @if ($project->place || $project->date)
            <div class="projectShowInfoUnderCoverMobile">
                @if ($project->place)
                    <p class="projectShowText">{{ $project->place }}</p>
                @endif

                @if ($project->date)
                    <p class="projectShowText">{{ $project->date }}</p>
                @endif
            </div>
        @endif

        <aside class="projectShowMeta">
            @if ($project->title)
                <h1 class="projectShowTitle">{{ $project->title }}</h1>
            @endif

            @foreach (($project->meta['credits'] ?? []) as $credit)
                @if (!empty($credit['role']) || !empty($credit['name']))
                    <p class="projectShowText projectShowCredit">
                        @if (!empty($credit['role']))
                            <span class="projectShowRole">{{ $credit['role'] }}</span>
                        @endif
                        @if (!empty($credit['name']))
                            <span class="projectShowName">{{ $credit['name'] }}</span>
                        @endif
                    </p>
                @endif
            @endforeach
        </aside>

        @if ($project->photos->isNotEmpty())
            <section class="projectShowGallery" aria-label="Project gallery">
                @foreach ($project->photos as $photo)
                    <div class="projectShowGalleryItem projectShowGalleryItemPending">
                        <img
                            src="{{ asset($photo->path) }}"
                            alt="{{ $photo->alt ?: ($project->title ? $project->title . ' photo' : 'Project photo') }}"
                            class="projectShowGalleryImage"
                            loading="eager"
                            decoding="async"
                        >
                    </div>
                @endforeach
            </section>
        @endif
    </section>
</x-layout>
