<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingRecipientDelivery;
use Illuminate\Http\Response;

class EmailOpenTrackingController extends Controller
{
    private const ONE_BY_ONE_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function pixel(string $token): Response
    {
        $row = EmailMarketingRecipientDelivery::query()
            ->where('tracking_token', $token)
            ->first();
        if ($row !== null) {
            $before = (int) $row->open_count;
            $row->increment('open_count');
            if ($before === 0) {
                $row->update(['first_opened_at' => now()]);
            }
        }

        $binary = base64_decode(self::ONE_BY_ONE_GIF, true) ?: '';
        if ($binary === '') {
            $binary = base64_decode(self::ONE_BY_ONE_GIF);
        }

        return response($binary, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
