<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Service;

interface PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $password): string;

    public function verify(string $hash, #[\SensitiveParameter] string $password): bool;

    public function needsRehash(string $hash): bool;
}
