<x-layout :title="($project->title ?: 'Project') . ' — Maria Sofia Brini'" :barba="true" namespace="project">
        <x-slot:persistent>
        @guest
        <x-navbar :title="'MARIA SOFIA BRINI'" :show-info-link="true" />
        @endguest
        </x-slot:persistent>

        @auth
        <nav>
            <a href="{{ route('home') }}" class="backendBrand">
                Hello Maria Sofia
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf

                <button class="logoutButton logoutButtonThirdCol" type="submit">
                    <ion-icon name="arrow-back-sharp"></ion-icon>
                    <span>Logout</span>
                </button>
            </form>

            <a class="infoA" href="{{ route('info') }}">
                INFO
            </a>
        </nav>
        @endauth

        <section class="projectShow">
            <a href="{{ route('home') }}" class="projectShowClose" aria-label="Close project">
                <ion-icon name="close-sharp" aria-hidden="true"></ion-icon>
            </a>

            <div class="projectShowMain">
                @if ($project->cover_image)
                    <img
                        src="{{ Storage::url($project->cover_image) }}"
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
                                src="{{ asset('storage/' . $photo->path) }}"
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

        @auth
        <div class="projectShowBottomActions">
            <a href="{{ route('admin.projects.edit', $project) }}" class="projectShowBottomAction projectShowBottomActionSecondCol">
                Edit
            </a>

            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="projectShowBottomActionForm" onsubmit="return confirm('Do you really want to delete {{ addslashes($project->title ?: 'this project') }}?');">
                @csrf
                @method('DELETE')
                <button class="projectShowBottomAction projectShowBottomActionThirdCol" type="submit">
                    Delete
                </button>
            </form>
        </div>
        @endauth
</x-layout>
