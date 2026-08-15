<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

use Shared\Application\Port\DrivingPortOutcomeInterface;

final readonly class GeneratedApiToken implements DrivingPortOutcomeInterface
{
    public function __construct(
        public string $identifier,
        public string $secret,
    ) {
    }
}
