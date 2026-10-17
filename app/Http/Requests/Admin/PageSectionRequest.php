<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_order' => (int) $this->input('sort_order', 0), 'is_active' => $this->boolean('is_active')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['required', Rule::in(array_keys(config('site.sections')))],
            'section' => ['required', 'string', 'max:50'],
            'badge' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'secondary_button_text' => ['nullable', 'string', 'max:100'],
            'secondary_button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The section must be one the public page template renders.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $sections = config('site.sections.'.$this->input('page'), []);

                if (! array_key_exists($this->input('section'), $sections)) {
                    $validator->errors()->add('section', 'Choose a section that exists on this page.');
                }
            },
        ];
    }
}
