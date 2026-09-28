<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Application\TotpEnrollment\TotpProvisioningInterface;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

final readonly class OtphpTotpProvisioning implements TotpProvisioningInterface
{
    // 20 bytes (160 bits): the RFC 4226/6238 baseline, encoding to a 32-character Base32 key
    // short enough for manual entry — OTPHP's own default (64 bytes) is unusable typed by hand.
    private const int SECRET_SIZE = 20;

    public function __construct(private ClockInterface $clock)
    {
    }

    public function generateSecret(): string
    {
        $secret = TOTP::generate($this->clock, self::SECRET_SIZE)->getSecret();
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
