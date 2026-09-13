<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\ApiKeyIssuance;

final readonly class GeneratedApiKey
{
    public function __construct(
        public string $keyId,
        public string $secret,
    ) {
    }
}
