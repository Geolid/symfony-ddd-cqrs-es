<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Domain\Service\SecretHasherInterface;

final class SecretHasher implements SecretHasherInterface
{
    public function hash(string $secret): string
    {
        return password_hash($secret, \PASSWORD_ARGON2ID);
    }

    public function verify(string $hash, string $secret): bool
    {
        return password_verify($secret, $hash);
    }
}
