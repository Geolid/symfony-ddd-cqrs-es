<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\UnenrollTotp;

use Shared\Application\Command\CommandInterface;

final readonly class UnenrollTotp implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
    ) {
    }
}
