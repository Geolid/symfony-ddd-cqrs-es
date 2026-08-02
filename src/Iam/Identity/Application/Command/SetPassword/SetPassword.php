<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SetPassword;

use Shared\Application\Command\CommandInterface;

final readonly class SetPassword implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $hash,
    ) {
    }
}
