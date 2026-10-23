<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(public SiteSettings $settings) {}

    /**
     * Every settings tab with its fields and current values.
     */
    public function index(): JsonResponse
    {
        $groups = [];

        foreach (config('site.settings') as $group => $groupConfig) {
            $fields = [];

            foreach ($groupConfig['fields'] as $key => $field) {
                $fields[] = [
                    'key' => $key,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'value' => $field['type'] === 'image' ? $this->settings->image($key) : $this->settings->get($key),
                ];
            }

            $groups[] = ['key' => $group, 'label' => $groupConfig['label'], 'fields' => $fields];
        }

        return response()->json(['groups' => $groups]);
    }

    public function update(Request $request, string $group): JsonResponse
    {
        $fields = config("site.settings.{$group}.fields");

        abort_unless(is_array($fields), 404);

        $request->validate($this->settings->validationRules($fields));

        $this->settings->set($this->settings->valuesFromRequest($request, $fields));

        return response()->json(['message' => 'Settings saved.']);
    }
}
