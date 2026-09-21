<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;

final class FakeTotpBackupCodeHasher implements TotpBackupCodeHasherInterface
{
    private const string PREFIX = 'hashed:';

    public function hash(#[\SensitiveParameter] string $code): string
    {
        return self::PREFIX.$code;
    }

    public function verify(#[\SensitiveParameter] string $code, string $hashedCode): bool
    {
        return self::PREFIX.$code === $hashedCode;
    }
}
