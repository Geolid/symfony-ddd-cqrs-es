<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\GrantPermission;

use Shared\Application\Command\CommandInterface;

final readonly class GrantPermission implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $permission,
    ) {
    }
}
