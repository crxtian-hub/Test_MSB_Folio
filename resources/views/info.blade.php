<x-layout title="Maria Sofia Brini — info" :barba="true" namespace="info">
        @php
            $subtitle = $infoPage?->subtitle ?: 'Stylist & Creative Consultant';
            $email = $infoPage?->email ?: 'sofia.brini@gmail.com';
            $instagramUrl = $infoPage?->instagram_url ?: 'https://www.instagram.com/sofiabrini__/';
            $credits = data_get($infoPage, 'meta.credits', [
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
            ]);
            $sections = data_get($infoPage, 'meta.sections', []);
        @endphp

        <x-slot:persistent>
        @guest
        <x-navbar :title="'MARIA SOFIA BRINI'" :subtitle="$subtitle" :show-info-link="true" :info-active="true" />
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

        <a href="{{ route('admin.info.edit') }}" class="addProjectButton infoEditButton" aria-label="Edit info">
            EDIT
        </a>
        @endauth

        <section class="infoPage">
            <a href="{{ route('home') }}" class="projectShowClose" aria-label="Close info">
                <ion-icon name="close-sharp" aria-hidden="true"></ion-icon>
            </a>

            @if ($infoPage?->photo_path)
                <img
                    src="{{ asset('storage/' . $infoPage->photo_path) }}"
                    alt="Maria Sofia Brini"
                    class="infoPhoto"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="infoColumnRect" aria-hidden="true"></div>
            @endif
            <div class="infoContacts" aria-label="Contacts">
                <a href="mailto:{{ $email }}">
                    EMAIL
                    <ion-icon name="arrow-up-sharp" aria-hidden="true"></ion-icon>
                </a>
                <a href="{{ $instagramUrl }}" target="_blank" rel="noreferrer">
                    INSTAGRAM
                    <ion-icon name="arrow-up-sharp" aria-hidden="true"></ion-icon>
                </a>
            </div>
            <div class="infoCredits">
                @foreach ($credits as $credit)
                    @if (!empty($credit['name']))
                        @if (!empty($credit['url']))
                            <a class="infoCreditsName" href="{{ $credit['url'] }}" target="_blank" rel="noreferrer">
                                {{ $credit['name'] ?? '' }}
                                <ion-icon name="arrow-up-sharp"></ion-icon>
                            </a>
                        @else
                            <span class="infoCreditsName">{{ $credit['name'] ?? '' }}</span>
                        @endif
                    @endif
                @endforeach
            </div>

            @if (!empty($sections))
                <div class="infoSections">
                    @foreach ($sections as $section)
                        @if (!empty($section['title']) || !empty($section['description']))
                            <article class="infoSection">
                                @if (!empty($section['title']))
                                    <h2 class="infoSectionTitle">{{ $section['title'] }}</h2>
                                @endif
                                @if (!empty($section['description']))
                                    <p class="infoSectionText">{!! nl2br(e($section['description'])) !!}</p>
                                @endif
                            </article>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
</x-layout>
