<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;

final class FakeTotpCipher implements TotpCipherInterface
{
    private const string PREFIX = 'encrypted:';

    public function encrypt(#[\SensitiveParameter] string $secret): string
    {
        return base64_encode(self::PREFIX.$secret);
    }

    public function decrypt(string $encryptedSecret): string
    {
        $decoded = base64_decode($encryptedSecret, true);
        \assert(false !== $decoded);

        return substr($decoded, \strlen(self::PREFIX));
    }
}
