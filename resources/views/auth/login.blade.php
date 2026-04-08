<x-layout>
    <body>
        <title>MRSF&mdash;Login</title>
        <x-navbar />
        
        
        
        @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif
        
        <form method="POST" action="{{ route('login') }}">
            @csrf
            {{--! email fissa admin --}}
            <input type="hidden" name="email" value="{{ config('admin.email') }}">
            {{-- <p>DEBUG email: {{ config('admin.email') }}</p> --}}
            
            <div class="pwLoginContainer">
                <div>
                    <input placeholder="Password" type="password" name="password" required autofocus>
                </div>
                <button type="submit">
                    <ion-icon name="arrow-forward-sharp"></ion-icon>
                </button>
            </div>
            
        </form>
    </body>
    
    
</x-layout>
