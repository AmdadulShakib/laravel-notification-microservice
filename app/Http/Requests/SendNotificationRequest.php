<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validates incoming notification send requests.
 * Returns structured JSON errors instead of redirects (API-friendly).
 */
class SendNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:sms,email,whatsapp'],
            'recipient' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'metadata.campaign_id' => ['nullable', 'string'],
            'metadata.campaign_name' => ['nullable', 'string'],
            'metadata.priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The notification type must be one of: sms, email, whatsapp.',
            'user_id.min' => 'The user_id must be a positive integer.',
            'message.max' => 'The message cannot exceed 5000 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt — return JSON instead of redirect.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
