<?php

namespace App\Http\Requests;

use App\Enums\UserNotificationFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->notification->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'frequency' => ['sometimes', Rule::enum(UserNotificationFrequency::class)],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
