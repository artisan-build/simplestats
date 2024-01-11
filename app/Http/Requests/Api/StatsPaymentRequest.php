<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StatsPaymentRequest extends FormRequest
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
            'stats_user_id' => ['required', 'integer', 'exists:stats_users,id'],
            'gross' => ['required', 'numeric'],
            'net' => ['required', 'numeric'],
            'time' => ['required', 'date'],
        ];
    }
}
