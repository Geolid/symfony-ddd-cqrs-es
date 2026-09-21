<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final readonly class SymfonyTotpBackupCodeHasher implements TotpBackupCodeHasherInterface
{
    public function __construct(private NativePasswordHasher $hasher)
    {
    }

    public function hash(#[\SensitiveParameter] string $code): string
    {
        return $this->hasher->hash($code);
    }

    public function verify(#[\SensitiveParameter] string $code, string $hashedCode): bool
    {
        return $this->hasher->verify($hashedCode, $code);
    }
}
