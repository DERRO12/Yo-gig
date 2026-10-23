<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PageSectionResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TestimonialResource;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\PageSection;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public website content, so the app always shows what the super admin published.
 */
class ContentController extends Controller
{
    public function __construct(public SiteSettings $settings) {}

    /**
     * Branding, contact details, payment accounts and the labels the app needs.
     */
    public function config(): JsonResponse
    {
        $settings = [];

        foreach ($this->settings->definitions() as $key => $field) {
            $settings[$key] = match ($field['type']) {
                'image' => $this->settings->image($key),
                'boolean' => $this->settings->bool($key),
                'number' => $this->settings->number($key),
                default => $this->settings->get($key),
            };
        }

        return response()->json([
            'settings' => $settings,
            'payment_methods' => Payment::METHODS,
            'booking_statuses' => Job::STATUSES,
            'payment_statuses' => Job::PAYMENT_STATUSES,
            'pages' => config('site.sections'),
        ]);
    }

    /**
     * Content blocks of a website page grouped by section, plus the listings that page shows.
     */
    public function page(string $page): JsonResponse
    {
        abort_unless(array_key_exists($page, config('site.sections')), 404);

        $data = [
            'page' => $page,
            'sections' => PageSection::forPage($page)
                ->map(fn ($blocks) => PageSectionResource::collection($blocks)),
        ];

        if ($page === 'home') {
            $data['featured_services'] = ServiceResource::collection(
                Service::query()->active()->where('is_featured', true)->ordered()->take(3)->get()
            );
            $data['fresh_gigs'] = BookingResource::collection(
                Job::query()->availableForMaids()->with('service')->latest()->take(6)->get()
            );
            $data['total_open_gigs'] = Job::query()->availableForMaids()->count();
            $data['testimonials'] = TestimonialResource::collection(
                Testimonial::query()->active()->orderBy('sort_order')->get()
            );
        }

        if ($page === 'services') {
            $data['services'] = ServiceResource::collection(Service::query()->active()->ordered()->get());
        }

        return response()->json($data);
    }

    public function services(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::query()->active()->ordered()->get());
    }

    public function service(Service $service): ServiceResource
    {
        abort_unless($service->is_active, 404);

        return ServiceResource::make($service);
    }

    public function testimonials(): AnonymousResourceCollection
    {
        return TestimonialResource::collection(Testimonial::query()->active()->orderBy('sort_order')->get());
    }

    public function contact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        ContactMessage::query()->create($validated);

        return response()->json(['message' => 'Thanks for reaching out! Our team will get back to you shortly.'], 201);
    }
}
