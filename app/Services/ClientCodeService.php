<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Str;

class ClientCodeService
{
    /**
     * Unique short client reference per company (e.g. C-A1B2C3).
     */
    public function assignIfMissing(Client $client): void
    {
        if (filled($client->code)) {
            return;
        }

        $companyId = $client->company_id;
        do {
            $code = 'C-'.strtoupper(Str::random(6));
        } while (Client::query()->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->exists());

        $client->forceFill(['code' => $code])->saveQuietly();
    }
}
