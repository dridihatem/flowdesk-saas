<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private readonly Google2FA $google2fa;

    public function __construct(?Google2FA $google2fa = null)
    {
        $this->google2fa = $google2fa ?? new Google2FA;
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(string $companyLabel, string $holderEmail, #[\SensitiveParameter] string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl($companyLabel, $holderEmail, $secret);

        $writer = new Writer(new ImageRenderer(
            new RendererStyle(220, 2),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($url);
    }

    public function verifyOtp(#[\SensitiveParameter] string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * @return array{0: list<string>, 1: list<string>} Plain codes (show once), hashed for storage
     */
    public function createRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        for ($i = 0; $i < $count; $i++) {
            $plain[] = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
        }

        $hashed = array_map(fn (string $c) => Hash::make($c), $plain);

        return [$plain, $hashed];
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        /** @var list<string>|null $hashes */
        $hashes = $user->two_factor_recovery_codes;
        if ($hashes === null || $hashes === []) {
            return false;
        }

        $normalized = strtoupper(str_replace(' ', '', $code));

        foreach ($hashes as $i => $hash) {
            if (Hash::check($normalized, $hash)) {
                unset($hashes[$i]);
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($hashes),
                ])->save();

                return true;
            }
        }

        return false;
    }
}
