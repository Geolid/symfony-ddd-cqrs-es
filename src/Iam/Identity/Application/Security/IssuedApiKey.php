<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Port\DrivingPortOutcomeInterface;

final readonly class IssuedApiKey implements DrivingPortOutcomeInterface
{
    public function __construct(
        public string $identifier,
        public string $secret,
    ) {
    }
}
