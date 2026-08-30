<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\ApiKey;

final readonly class GeneratedApiKey
{
    public function __construct(
        public string $keyId,
        public string $secret,
    ) {
    }
}
