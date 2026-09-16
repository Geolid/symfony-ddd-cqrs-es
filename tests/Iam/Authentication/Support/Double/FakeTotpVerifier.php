<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;

final class FakeTotpVerifier implements TotpVerifierInterface
{
    public static function codeFor(string $secret): string
    {
        return substr(hash('crc32b', $secret), 0, 6);
    }

    public function verify(#[\SensitiveParameter] string $secret, string $code): bool
    {
        return $code === self::codeFor($secret);
    }
}
