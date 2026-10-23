<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageSectionRequest;
use App\Http\Resources\PageSectionResource;
use App\Models\PageSection;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageSectionController extends Controller
{
    /**
     * Every content block of one website page, including hidden ones.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $pages = config('site.sections');
        $page = array_key_exists((string) $request->input('page'), $pages) ? $request->input('page') : array_key_first($pages);

        return PageSectionResource::collection(
            PageSection::query()
                ->where('page', $page)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        )->additional([
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function store(PageSectionRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::store($request->file('image'), 'sections');
        }

        $section = PageSection::query()->create($data);

        return PageSectionResource::make($section)
            ->additional(['message' => 'Content block created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(PageSectionRequest $request, PageSection $section): PageSectionResource
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::replace($section->image_path, $request->file('image'), 'sections');
        } elseif ($request->boolean('remove_image')) {
            Media::delete($section->image_path);
            $data['image_path'] = null;
        }

        $section->update($data);

        return PageSectionResource::make($section)->additional(['message' => 'Content block updated.']);
    }

    public function destroy(PageSection $section): JsonResponse
    {
        Media::delete($section->image_path);
        $section->delete();

        return response()->json(['message' => 'Content block deleted.']);
    }
}
