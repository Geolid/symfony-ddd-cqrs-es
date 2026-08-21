<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Service;

interface ApiKeyHasherInterface
{
    public function hash(#[\SensitiveParameter] string $secret): string;

    public function verify(string $hashedSecret, #[\SensitiveParameter] string $secret): bool;
}
