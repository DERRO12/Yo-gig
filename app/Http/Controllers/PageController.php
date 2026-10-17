<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('landing', [
            'sections' => PageSection::forPage('home'),
            'featuredServices' => Service::query()->active()->where('is_featured', true)->ordered()->take(3)->get(),
            'freshGigs' => Job::query()->availableForMaids()->with('service')->latest()->take(6)->get(),
            'totalOpenGigs' => Job::query()->availableForMaids()->count(),
            'testimonials' => Testimonial::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function about(): View
    {
        return view('about', [
            'sections' => PageSection::forPage('about'),
        ]);
    }

    public function services(): View
    {
        return view('services', [
            'sections' => PageSection::forPage('services'),
            'services' => Service::query()->active()->ordered()->get(),
        ]);
    }

    public function contact(): View
    {
        return view('contact', [
            'sections' => PageSection::forPage('contact'),
        ]);
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        ContactMessage::query()->create($validated);

        return redirect()->route('contact')->with('success', 'Thanks for reaching out! Our team will get back to you shortly.');
    }
}
