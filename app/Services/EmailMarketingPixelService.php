<?php

namespace App\Services;

class EmailMarketingPixelService
{
    public function newTrackingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function absoluteOpenUrl(string $token): string
    {
        return route('email-marketing.tracking.open', ['token' => $token], true);
    }

    public function appendTrackingPixel(string $html, string $token): string
    {
        $url = htmlspecialchars($this->absoluteOpenUrl($token), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $img = '<img src="'.$url.'" width="1" height="1" alt="" style="display:block;border:0;outline:0;height:1px;width:1px" />';

        if (preg_match('/<\/body>/i', $html)) {
            return (string) preg_replace('/<\/body>/i', $img.'</body>', $html, 1);
        }

        return $html.$img;
    }
}
