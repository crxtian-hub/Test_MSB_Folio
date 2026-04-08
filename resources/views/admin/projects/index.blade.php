<x-layout>

    <title>ciao</title>
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
        <h1>Progetti</h1>
        
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ route('admin.projects.create') }}">+ Nuovo progetto</a>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </div>
    
    @if (session('status'))
    <p style="color:green;">{{ session('status') }}</p>
    @endif
    
    @if ($projects->count() === 0)
    <p>Nessun progetto ancora. Crea il primo </p>
    <a href="{{ route('admin.projects.create') }}">Crea progetto</a>
    @else
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap:16px; margin-top:16px;">
        @foreach ($projects as $project)
        <div style="border:1px solid #ddd; padding:12px; border-radius:12px;">
            <div style="aspect-ratio: 4 / 3; background:#f3f3f3; border-radius:10px; overflow:hidden; margin-bottom:10px;">
                @if ($project->cover_image)
                <img
                src="{{ asset('storage/'.$project->cover_image) }}"
                alt="{{ $project->title ?? 'Cover progetto' }}"
                style="width:100%; height:100%; object-fit:cover;"
                >
                @else
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#777;">
                    Nessuna cover
                </div>
                @endif
            </div>
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                <div>
                    <strong>{{ $project->title ?? '(senza titolo)' }}</strong>
                    <div style="font-size:12px; color:#000000; margin-top:4px;">
                        Slug: {{ $project->slug }}
                    </div>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:12px; align-items:center;">
                <a href="{{ route('admin.projects.edit', $project) }}">Edit</a>
                
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                onsubmit="return confirm('Sicuro di eliminare questo progetto?');">
                @csrf
                @method('DELETE')
                <button type="submit">Delete</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

<div style="margin-top:16px;">
    {{ $projects->links() }}
</div>
@endif



</x-layout>
