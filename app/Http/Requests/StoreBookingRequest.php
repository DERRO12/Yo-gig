<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Support\SiteSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    /**
     * Services with a fixed price are priced automatically; quote-based ones use the client's budget.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $minimum = app(SiteSettings::class)->number('min_booking_amount');

                if ($this->totalAmount() < $minimum) {
                    $validator->errors()->add('budget', 'The booking total must be at least '.app(SiteSettings::class)->money($minimum).'.');
                }
            },
        ];
    }

    public function service(): Service
    {
        return Service::query()->findOrFail($this->integer('service_id'));
    }

    public function totalAmount(): float
    {
        $service = $this->service();

        if ($service->hasFixedPrice()) {
            return (float) $service->base_price * $this->integer('quantity');
        }

        return (float) $this->input('budget', 0);
    }
}
