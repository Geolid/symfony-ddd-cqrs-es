<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiTokenCredential;

use Shared\Application\Command\CommandInterface;

final readonly class RevokeApiTokenCredential implements CommandInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
