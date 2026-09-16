<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Application\TotpProvisioning\TotpProvisioningInterface;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

final readonly class OtphpTotpProvisioning implements TotpProvisioningInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function generateSecret(): string
    {
        $secret = TOTP::generate($this->clock)->getSecret();
        \assert('' !== $secret);

        return $secret;
    }

    public function provisioningUri(#[\SensitiveParameter] string $secret, string $label, string $issuer): string
    {
        $otp = TOTP::createFromSecret($secret, $this->clock);
        $otp->setLabel($label);
        $otp->setIssuer($issuer);

        return $otp->getProvisioningUri();
    }
}
