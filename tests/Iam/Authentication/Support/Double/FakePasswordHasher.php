<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;

final readonly class FakePasswordHasher implements PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        return $hashedPassword === $this->hash($plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
