<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Stub;

use Iam\Identity\Domain\Service\SecretHasherInterface;

final class DummySecretHasher implements SecretHasherInterface
{
    public function hash(string $secret): string
    {
        return 'hashed:'.$secret;
    }

    public function verify(string $hash, string $secret): bool
    {
        return $hash === $this->hash($secret);
    }

    public function needsRehash(string $hash): bool
    {
        return false;
    }
}
