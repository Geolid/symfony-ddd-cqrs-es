<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\RequestErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestErasure implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
    ) {
    }
}
