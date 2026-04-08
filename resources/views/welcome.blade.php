<x-layout title="Maria Sofia Brini — Fashion Stylist" :barba="true" namespace="home">
        <x-slot:persistent>
        @guest
        <x-navbar :show-info-link="true" />
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

            <a class="infoA" href="{{route('info')}}">
                INFO
            </a>

        </nav>
        
        
        <a href="{{ route('admin.projects.create') }}" class="addProjectButton" aria-label="Add project">
            <ion-icon name="add-sharp" aria-hidden="true"></ion-icon>
        </a>
        
        @endauth
        
        
        
        {{--todo questo da mettere nelle pagine di dettaglio
        @auth
        <button>Modifica</button>
        <form method="POST" action="{{ route('projects.destroy', $project) }}">
            @csrf
            @method('DELETE')
            <button>Elimina</button>
        </form>
        @endauth --}}
        <section
            class="projectsGrid"
            aria-label="Projects"
            @auth
                data-sort-enabled="true"
                data-sort-endpoint="{{ route('admin.projects.reorder') }}"
            @endauth
        >
            @forelse ($projects as $project)
                <div class="projectCard" data-project-id="{{ $project->id }}">
                    <div class="projectCardMedia">
                        <a
                            href="{{ route('projects.show', ['project' => $project->slug]) }}"
                            class="projectCardLink"
                            data-project-key="{{ $project->slug }}"
                        >
                            @if ($project->cover_image)
                                <img
                                    src="{{ Storage::url($project->cover_image) }}"
                                    alt="{{ $project->title ? $project->title . ' cover' : 'Project cover' }}"
                                    class="projectCover"
                                    data-project-key="{{ $project->slug }}"
                                    loading="eager"
                                    decoding="async"
                                >
                            @endif
                            @guest
                            <div class="projectCardOverlay">
                                <span class="projectCardTitle">{{ $project->title }}</span>
                            </div>
                            @endguest
                        </a>
                        @auth
                        <div class="projectCardActions">
                            <button type="button" class="projectCardSortHandle" aria-label="Drag to reorder" title="Drag to reorder">
                                <ion-icon name="reorder-three-sharp" aria-hidden="true"></ion-icon>
                            </button>
                            <a href="{{ route('admin.projects.edit', $project) }}" class="projectCardAction">Edit</a>
                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="projectCardActionForm" onsubmit="return confirm('Do you really want to delete {{ addslashes($project->title ?: 'this project') }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="projectCardAction">Delete</button>
                            </form>
                        </div>
                        @endauth
                    </div>
                </div>
            @empty
                <p class="projectsEmpty">No projects yet.</p>
            @endforelse
        </section>

</x-layout>
