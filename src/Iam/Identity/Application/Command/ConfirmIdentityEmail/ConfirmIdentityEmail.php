<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ConfirmIdentityEmail;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmIdentityEmail implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $code,
    ) {
    }
}
