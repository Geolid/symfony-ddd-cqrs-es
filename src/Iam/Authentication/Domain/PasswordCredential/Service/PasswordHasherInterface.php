<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Service;

interface PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string;

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool;

    public function needsRehash(string $hashedPassword): bool;
}
