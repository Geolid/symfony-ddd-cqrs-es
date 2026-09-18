<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ConfirmIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmIdentity implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $code,
    ) {
    }
}
