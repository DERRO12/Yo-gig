<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageSectionRequest;
use App\Models\PageSection;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSectionController extends Controller
{
    public function index(Request $request): View
    {
        $pages = config('site.sections');
        $page = array_key_exists($request->input('page'), $pages) ? $request->input('page') : array_key_first($pages);

        return view('admin.sections.index', [
            'pages' => $pages,
            'page' => $page,
            'sections' => PageSection::query()
                ->where('page', $page)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('section'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.sections.form', [
            'section' => new PageSection([
                'page' => $request->input('page', 'home'),
                'section' => $request->input('section'),
                'is_active' => true,
            ]),
            'pages' => config('site.sections'),
        ]);
    }

    public function store(PageSectionRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::store($request->file('image'), 'sections');
        }

        $section = PageSection::query()->create($data);

        return redirect()->route('admin.sections.index', ['page' => $section->page])->with('success', 'Content block created.');
    }

    public function edit(PageSection $section): View
    {
        return view('admin.sections.form', [
            'section' => $section,
            'pages' => config('site.sections'),
        ]);
    }

    public function update(PageSectionRequest $request, PageSection $section): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = Media::replace($section->image_path, $request->file('image'), 'sections');
        } elseif ($request->boolean('remove_image')) {
            Media::delete($section->image_path);
            $data['image_path'] = null;
        }

        $section->update($data);

        return redirect()->route('admin.sections.index', ['page' => $section->page])->with('success', 'Content block updated.');
    }

    public function destroy(PageSection $section): RedirectResponse
    {
        Media::delete($section->image_path);
        $section->delete();

        return back()->with('success', 'Content block deleted.');
    }
}
