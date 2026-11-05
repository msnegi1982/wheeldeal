<?php

namespace App\Http\Requests;

use App\Enums\OtpChannel;
use App\Enums\VehicleCondition;
use App\Rules\Recaptcha;
use App\Rules\ValidPhone;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVehicleRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('type') && $this->route('type')) {
            $this->merge(['type' => $this->route('type')]);
        }

        if (! $this->filled('channel')) {
            $this->merge(['channel' => OtpChannel::SMS->value]);
        }

        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize((string) $this->input('phone'))]);
        }

        $this->merge([
            'consent' => $this->boolean('consent'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSell = $this->input('type') === 'sell';
        $requiredForSell = $isSell ? 'required' : 'nullable';
        $maxPhotos = (int) config('marketplace.uploads.max_request_photos', 6);
        $maxKb = (int) config('marketplace.uploads.max_image_kb', 5120);

        return [
            'type' => ['required', Rule::in(['sell', 'buy'])],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', new ValidPhone],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'brand_id' => [$requiredForSell, 'integer', 'exists:brands,id'],
            'vehicle_model_id' => [$requiredForSell, 'integer', 'exists:vehicle_models,id'],
            'vehicle_variant_id' => ['nullable', 'integer', 'exists:vehicle_variants,id'],
            'year' => [$requiredForSell, 'integer', 'between:1980,'.(date('Y') + 1)],
            'km_driven' => [$requiredForSell, 'integer', 'min:0', 'max:1000000'],
            'expected_price' => [$requiredForSell, 'integer', 'min:0', 'max:100000000'],
            'condition' => ['nullable', Rule::in(array_keys(VehicleCondition::options()))],
            'colour' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'channel' => ['required', Rule::in(array_keys(OtpChannel::options()))],
            'photos' => ['nullable', 'array', 'max:'.$maxPhotos],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'website' => ['nullable', 'prohibited'],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            (new Recaptcha)->validate(
                'captcha_token',
                $this->input('captcha_token'),
                fn (string $message) => $validator->errors()->add('captcha_token', $message)
            );
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'phone' => 'mobile number',
            'city_id' => 'city',
            'brand_id' => 'brand',
            'vehicle_model_id' => 'model',
            'vehicle_variant_id' => 'variant',
            'km_driven' => 'kilometres driven',
            'expected_price' => 'expected price',
            'consent' => 'consent',
            'photos.*' => 'photo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'This field must be left empty.',
            'consent.accepted' => 'Please agree to be contacted about this request.',
            'brand_id.required' => 'Select the vehicle brand.',
            'vehicle_model_id.required' => 'Select the vehicle model.',
            'year.required' => 'Enter the manufacturing year.',
            'km_driven.required' => 'Enter the kilometres driven.',
            'expected_price.required' => 'Enter your expected price.',
        ];
    }
}
