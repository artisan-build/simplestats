<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignLinkRequest;
use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Models\StatsTrackCampaign;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService,
    ) {}

    public function store(CampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaignService->create(
            (int) $request->validated('project_id'),
            $request->validated('name'),
        );

        return redirect()->route('campaigns.show', $campaign);
    }

    public function linkTrackCampaign(CampaignLinkRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->campaignService->linkTrackCampaign($campaign, $request->validated('name'));

        return redirect()->back();
    }

    public function unlinkTrackCampaign(Campaign $campaign, StatsTrackCampaign $trackCampaign): RedirectResponse
    {
        $this->campaignService->unlinkTrackCampaign($campaign, $trackCampaign);

        return redirect()->back();
    }
}
