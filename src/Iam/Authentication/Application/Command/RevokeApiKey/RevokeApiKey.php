<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeApiKey;

use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Command\CommandInterface;

final readonly class RevokeApiKey implements CommandInterface, ActingIdentityAware
{
    public function __construct(
        public string $id,
        public string $identityId,
    ) {
    }

    public function actingIdentityId(): string
    {
        return $this->identityId;
    }
}
