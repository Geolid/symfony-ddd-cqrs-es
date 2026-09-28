<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NativeBackupCodeHasher implements BackupCodeHasherInterface
{
    public function __construct(
        #[Autowire('%env(BACKUP_CODE_SECRET)%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }

    public function verify(string $hashedCode, #[\SensitiveParameter] string $plainCode): bool
    {
        return hash_equals($hashedCode, $this->hash($plainCode));
    }
}
