<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Media;
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

        $request->validate($this->rules($fields));

        $values = [];

        foreach ($fields as $key => $field) {
            $values[$key] = match ($field['type']) {
                'boolean' => $request->boolean($key) ? '1' : '0',
                'image' => $request->hasFile($key)
                    ? Media::replace($this->settings->all()[$key] ?? null, $request->file($key), 'branding')
                    : ($this->settings->all()[$key] ?? null),
                default => $request->input($key),
            };
        }

        $this->settings->set($values);

        return redirect()->route('admin.settings.edit', $group)->with('success', 'Settings saved.');
    }

    /**
     * @param  array<string, array{type: string}>  $fields
     * @return array<string, list<string>>
     */
    protected function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $rules[$key] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:255'],
                'number' => ['nullable', 'numeric', 'min:0'],
                'boolean' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
                'textarea' => ['nullable', 'string', 'max:5000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }
}
