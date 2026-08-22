<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;

final readonly class ApiKeyHasher implements ApiKeyHasherInterface
{
    public function hash(#[\SensitiveParameter] string $secret): string
    {
        return hash('sha256', $secret);
    }

    public function verify(string $hashedSecret, #[\SensitiveParameter] string $secret): bool
    {
        return hash_equals($hashedSecret, $this->hash($secret));
    }
}
