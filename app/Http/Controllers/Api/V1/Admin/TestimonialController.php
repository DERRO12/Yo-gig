<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestimonialController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TestimonialResource::collection(Testimonial::query()->orderBy('sort_order')->get());
    }

    public function store(TestimonialRequest $request): JsonResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = Media::store($request->file('photo'), 'testimonials');
        }

        $testimonial = Testimonial::query()->create($data);

        return TestimonialResource::make($testimonial)
            ->additional(['message' => 'Testimonial created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = Media::replace($testimonial->photo_path, $request->file('photo'), 'testimonials');
        }

        $testimonial->update($data);

        return TestimonialResource::make($testimonial)->additional(['message' => 'Testimonial updated.']);
    }

    public function destroy(Testimonial $testimonial): JsonResponse
    {
        Media::delete($testimonial->photo_path);
        $testimonial->delete();

        return response()->json(['message' => 'Testimonial deleted.']);
    }
}
