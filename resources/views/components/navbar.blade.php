@props([
    'title' => 'MARIA SOFIA BRINI',
    'subtitle' => null,
    'showInfoLink' => false,
    'infoActive' => false,
])

<nav class="navbarGuest{{ $infoActive ? ' navbarGuestInfo' : '' }}" data-persistent-nav>
    <a href="{{ route('home') }}" class="hMRSF{{ $subtitle ? ' hMRSFWithSubtitle' : '' }}">
        <span class="hMRSFTitle">{{ $title }}</span>
        @if ($subtitle)
            <span class="hMRSFSubtitle">{{ $subtitle }}</span>
        @endif
    </a>

    @if ($showInfoLink)
        @if ($infoActive)
            <span class="infoA infoAActive" aria-current="page">INFO</span>
        @else
            <a class="infoA" href="{{ route('info') }}">
                INFO
            </a>
        @endif
    @endif
</nav>
