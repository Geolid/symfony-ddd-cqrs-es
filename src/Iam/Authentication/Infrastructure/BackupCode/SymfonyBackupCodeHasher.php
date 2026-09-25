<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final readonly class SymfonyBackupCodeHasher implements BackupCodeHasherInterface
{
    public function __construct(private NativePasswordHasher $hasher)
    {
    }

    public function hash(#[\SensitiveParameter] string $code): string
    {
        return $this->hasher->hash($code);
    }

    public function verify(string $hashedCode, #[\SensitiveParameter] string $plainCode): bool
    {
        return $this->hasher->verify($hashedCode, $plainCode);
    }
}
