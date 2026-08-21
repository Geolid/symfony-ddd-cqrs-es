<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Doubles;

use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;

final class StubApiKeyHasher implements ApiKeyHasherInterface
{
    public function hash(#[\SensitiveParameter] string $secret): string
    {
        return str_pad(substr('hashed:'.$secret, 0, 64), 64, '*');
    }

    public function verify(string $hashedSecret, #[\SensitiveParameter] string $secret): bool
    {
        return $hashedSecret === $this->hash($secret);
    }
}
