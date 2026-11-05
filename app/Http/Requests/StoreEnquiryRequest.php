<?php

namespace App\Http\Requests;

use App\Rules\Recaptcha;
use App\Rules\ValidPhone;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize((string) $this->input('phone'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', new ValidPhone],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
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
            'message' => 'message',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'This field must be left empty.',
            'message.min' => 'Tell us a little more about what you are looking for (at least 10 characters).',
        ];
    }
}
