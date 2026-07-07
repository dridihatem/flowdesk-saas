<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingCampaign;
use App\Services\EmailMarketingCampaignSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailMarketingPreviewEmailController extends Controller
{
    public function __invoke(Request $request, EmailMarketingCampaignSendService $sendService): JsonResponse
    {
        $company = $request->user()?->company;
        if (! $company) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'sample_to' => ['required', 'string', 'max:255', 'email'],
            'subject' => ['nullable', 'string', 'max:998'],
            'body_html' => ['required', 'string'],
            'campaign_id' => [
                'nullable',
                'string',
                Rule::exists('email_marketing_campaigns', 'id')->where('company_id', $company->id),
            ],
        ]);

        $campaign = null;
        if (! empty($data['campaign_id'])) {
            $campaign = EmailMarketingCampaign::query()->whereKey($data['campaign_id'])->first();
        }

        try {
            $sendService->sendPreviewEmail(
                $company,
                (string) $data['sample_to'],
                (string) ($data['subject'] ?? ''),
                (string) $data['body_html'],
                $campaign,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('email_marketing_sample_sent')]);
    }
}
