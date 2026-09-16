<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

final readonly class OtphpTotpVerifier implements TotpVerifierInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function verify(#[\SensitiveParameter] string $secret, string $code): bool
    {
        \assert('' !== $secret);

        if ('' === $code) {
            return false;
        }

        return TOTP::createFromSecret($secret, $this->clock)->verify($code);
    }
}
