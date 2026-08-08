<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Domain\Service\SecretHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final readonly class SecretHasher implements SecretHasherInterface
{
    public function __construct(private NativePasswordHasher $passwordHasher)
    {
    }

    public function hash(#[\SensitiveParameter] string $secret): string
    {
        return $this->passwordHasher->hash($secret);
    }

    public function verify(string $hash, #[\SensitiveParameter] string $secret): bool
    {
        return $this->passwordHasher->verify($hash, $secret);
    }

    public function needsRehash(string $hash): bool
    {
        return $this->passwordHasher->needsRehash($hash);
    }
}
