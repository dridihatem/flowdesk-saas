<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingAudience;
use App\Models\EmailMarketingCampaign;
use App\Models\EmailMarketingRecipientDelivery;
use App\Models\EmailMarketingSequence;
use App\Models\EmailMarketingTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $cid = (string) $company->id;
        $sentTotal = EmailMarketingRecipientDelivery::query()
            ->where('company_id', $cid)
            ->where('kind', EmailMarketingRecipientDelivery::KIND_MASS)
            ->count();
        $openedTotal = EmailMarketingRecipientDelivery::query()
            ->where('company_id', $cid)
            ->where('kind', EmailMarketingRecipientDelivery::KIND_MASS)
            ->whereNotNull('first_opened_at')
            ->count();
        $openRate = $sentTotal > 0 ? round(100 * $openedTotal / $sentTotal, 1) : null;

        return view('email-marketing.dashboard', [
            'campaignsCount' => EmailMarketingCampaign::query()->count(),
            'templatesCount' => EmailMarketingTemplate::query()->count(),
            'audiencesCount' => EmailMarketingAudience::query()->count(),
            'sequencesCount' => EmailMarketingSequence::query()->count(),
            'emailsSentTotal' => $sentTotal,
            'emailsOpenedTotal' => $openedTotal,
            'emailOpenRate' => $openRate,
        ]);
    }
}
