<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiToken;

use Shared\Application\Command\CommandInterface;

final readonly class RevokeApiToken implements CommandInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
