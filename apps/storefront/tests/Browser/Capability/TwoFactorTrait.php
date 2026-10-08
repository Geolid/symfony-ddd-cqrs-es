<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser\Capability;

use OTPHP\TOTP;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Symfony\Component\Clock\Clock;

trait TwoFactorTrait
{
    public function completeTwoFactorChallenge(string $code, bool $trustDevice = false): self
    {
        $this->use(static function (TwoFactorForm $twoFactor) use ($code, $trustDevice): void {
            $twoFactor->fillCode($code);

            if ($trustDevice) {
                $twoFactor->checkTrustDevice();
            }

            $twoFactor->submit();
        });

        return $this;
    }

    public function totpCode(string $secret): string
    {
        \assert('' !== $secret);

        return TOTP::createFromSecret($secret, Clock::get())->now();
    }
}
