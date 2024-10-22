<?php

namespace App\Http\Requests\Api;

use App\Models\StatsVisitor;
use App\Validators\StatsVisitorValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

class StatsVisitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge((new StatsVisitorValidator())->prepareForValidation($this->all()));

        if (! $this->has('visitor_hash')) {
            $this->merge(['visitor_hash' =>
                SimplestatsClient::createVisitorHash($this->time, $this->ip ?? '', $this->user_agent ?? '')
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return (new StatsVisitorValidator())->rules();
    }
}
