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

    public function hash(#[\SensitiveParameter] string $password): string
    {
        return $this->passwordHasher->hash($password);
    }

    public function verify(string $hash, #[\SensitiveParameter] string $password): bool
    {
        return $this->passwordHasher->verify($hash, $password);
    }

    public function needsRehash(string $hash): bool
    {
        return $this->passwordHasher->needsRehash($hash);
    }
}
