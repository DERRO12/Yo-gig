<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->withCount('jobs')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['is_active' => true, 'price_unit' => 'per visit'])]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::store($request->file('image'), 'services');
        }

        Service::query()->create($data);

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::replace($service->image_path, $request->file('image'), 'services');
        }

        $service->update($data);

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->jobs()->exists()) {
            $service->update(['is_active' => false]);

            return back()->with('success', 'Service has bookings, so it was hidden instead of deleted.');
        }

        Media::delete($service->image_path);
        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}
