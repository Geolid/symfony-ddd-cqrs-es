<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final readonly class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(private NativePasswordHasher $passwordHasher)
    {
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->passwordHasher->hash($plainPassword);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        return $this->passwordHasher->verify($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return $this->passwordHasher->needsRehash($hashedPassword);
    }
}
