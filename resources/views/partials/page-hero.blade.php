@if ($hero)
    <section class="page-hero" @if ($hero->imageUrl()) style="background-image: url('{{ $hero->imageUrl() }}');" @endif>
        <div class="container">
            @if ($hero->badge)
                <span class="hero__badge">{{ $hero->badge }}</span>
            @endif
            <h1>{{ $hero->title }}</h1>
            @if ($hero->body)
                <p>{{ $hero->body }}</p>
            @endif
        </div>
    </section>
@endif
