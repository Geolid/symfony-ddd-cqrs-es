<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ChangeEmail;

use Shared\Application\Command\CommandInterface;

final readonly class ChangeEmail implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $newEmail,
        public string $code,
    ) {
    }
}
