<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(public SiteSettings $settings) {}

    public function edit(?string $group = null): View
    {
        $groups = config('site.settings');
        $group ??= array_key_first($groups);

        abort_unless(isset($groups[$group]), 404);

        return view('admin.settings.edit', [
            'groups' => $groups,
            'group' => $group,
            'fields' => $groups[$group]['fields'],
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $fields = config("site.settings.{$group}.fields");

        abort_unless(is_array($fields), 404);

        $request->validate($this->settings->validationRules($fields));

        $this->settings->set($this->settings->valuesFromRequest($request, $fields));

        return redirect()->route('admin.settings.edit', $group)->with('success', 'Settings saved.');
    }
}
