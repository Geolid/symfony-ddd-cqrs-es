<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestEmailChange;

use Shared\Application\Command\CommandInterface;

final readonly class RequestEmailChange implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $newEmail,
    ) {
    }
}
