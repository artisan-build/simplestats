<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // 252 mirrors the Str::limit() the tracking pipeline applies to utm_campaign
        // (HasStatsTracking::handleStatsTypes), so a hand-linked dimension matches the
        // row incoming traffic would later firstOrCreate by team_id + name.
        return [
            'name' => ['required', 'string', 'max:252'],
        ];
    }
}
