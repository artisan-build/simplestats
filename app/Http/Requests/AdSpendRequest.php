<?php

namespace App\Http\Requests;

use App\Helpers\AppHelper;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdSpendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Stored amounts are integer cents; the form sends a major-unit value.
     * The campaign reference arrives as the campaign's public uuid.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => (int) round(((float) $this->amount) * 100)]);
        }

        $this->merge([
            'campaign_id' => AppHelper::resolvePublicId(Campaign::class, $this->input('campaign_id') ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teamId = AppHelper::getTeamId();

        return [
            'campaign_id' => ['required', 'integer', Rule::exists('campaigns', 'id')->where('team_id', $teamId)],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
