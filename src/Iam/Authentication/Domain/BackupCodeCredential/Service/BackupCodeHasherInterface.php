<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Service;

interface BackupCodeHasherInterface
{
    public function hash(#[\SensitiveParameter] string $code): string;

    public function verify(string $hashedCode, #[\SensitiveParameter] string $plainCode): bool;
}
