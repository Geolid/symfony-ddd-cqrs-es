<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;

final class FakeBackupCodeHasher implements BackupCodeHasherInterface
{
    private const string PREFIX = 'hashed:';

    public function hash(#[\SensitiveParameter] string $code): string
    {
        return self::PREFIX.$code;
    }

    public function verify(string $hashedCode, #[\SensitiveParameter] string $plainCode): bool
    {
        return self::PREFIX.$plainCode === $hashedCode;
    }
}
