@if ($cta)
    <section class="section">
        <div class="container">
            <div class="cta">
                <h2>{{ $cta->title }}</h2>
                @if ($cta->body)
                    <p>{{ $cta->body }}</p>
                @endif
                <div class="hero__actions">
                    @if ($cta->button_text)
                        <a href="{{ \App\Models\PageSection::link($cta->button_url) }}" class="btn btn-primary btn-lg">{{ $cta->button_text }}</a>
                    @endif
                    @if ($cta->secondary_button_text)
                        <a href="{{ \App\Models\PageSection::link($cta->secondary_button_url) }}" class="btn btn-outline btn-lg">{{ $cta->secondary_button_text }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
