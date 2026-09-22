<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Service;

interface BackupCodeHasherInterface
{
    public function hash(#[\SensitiveParameter] string $code): string;

    public function verify(#[\SensitiveParameter] string $code, string $hashedCode): bool;
}
