<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::query()->withCount('jobs')->ordered()->get());
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::store($request->file('image'), 'services');
        }

        $service = Service::query()->create($data);

        return ServiceResource::make($service)
            ->additional(['message' => 'Service created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::replace($service->image_path, $request->file('image'), 'services');
        }

        $service->update($data);

        return ServiceResource::make($service)->additional(['message' => 'Service updated.']);
    }

    public function destroy(Service $service): JsonResponse
    {
        if ($service->jobs()->exists()) {
            $service->update(['is_active' => false]);

            return response()->json(['message' => 'Service has bookings, so it was hidden instead of deleted.']);
        }

        Media::delete($service->image_path);
        $service->delete();

        return response()->json(['message' => 'Service deleted.']);
    }
}
