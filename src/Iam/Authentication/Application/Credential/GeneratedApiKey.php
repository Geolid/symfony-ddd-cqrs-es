<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Shared\Application\Port\DrivingPortOutcomeInterface;

final readonly class GeneratedApiKey implements DrivingPortOutcomeInterface
{
    public function __construct(
        public string $keyId,
        public string $secret,
    ) {
    }
}
