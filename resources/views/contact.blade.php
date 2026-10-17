@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
    @include('partials.page-hero', ['hero' => $sections->get('hero')?->first()])

    <section class="section">
        <div class="container">
            <div class="detail-grid">
                <div class="card card-body">
                    <h2 class="card-title mb-2">Send us a message</h2>
                    <form action="{{ route('contact.send') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your name</label>
                                <input type="text" id="name" name="name" class="input" value="{{ old('name', auth()->user()?->name) }}" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="input" value="{{ old('email', auth()->user()?->email) }}" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone (optional)</label>
                                <input type="text" id="phone" name="phone" class="input" value="{{ old('phone', auth()->user()?->phone_number) }}">
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" class="input" value="{{ old('subject') }}" placeholder="e.g. Question about a payment">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="input" rows="5" required>{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send message</button>
                    </form>
                </div>

                <aside class="card card-body">
                    <h2 class="card-title">Reach us directly</h2>
                    @if ($site->get('contact_phone'))
                        <div class="contact-item">
                            <div class="contact-item__icon">📞</div>
                            <div><strong>Phone</strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site->get('contact_phone')) }}">{{ $site->get('contact_phone') }}</a></div>
                        </div>
                    @endif
                    @if ($site->get('contact_whatsapp'))
                        <div class="contact-item">
                            <div class="contact-item__icon">💬</div>
                            <div><strong>WhatsApp</strong><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $site->get('contact_whatsapp')) }}" target="_blank" rel="noopener">{{ $site->get('contact_whatsapp') }}</a></div>
                        </div>
                    @endif
                    @if ($site->get('contact_email'))
                        <div class="contact-item">
                            <div class="contact-item__icon">✉️</div>
                            <div><strong>Email</strong><a href="mailto:{{ $site->get('contact_email') }}">{{ $site->get('contact_email') }}</a></div>
                        </div>
                    @endif
                    @if ($site->get('contact_address'))
                        <div class="contact-item">
                            <div class="contact-item__icon">📍</div>
                            <div><strong>Office</strong><span>{{ $site->get('contact_address') }}</span></div>
                        </div>
                    @endif
                    @if ($site->get('business_hours'))
                        <div class="contact-item">
                            <div class="contact-item__icon">🕒</div>
                            <div><strong>Hours</strong><span>{{ $site->get('business_hours') }}</span></div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
