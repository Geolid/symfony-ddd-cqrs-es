<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\AbortOrder;

use Shared\Application\Command\CommandInterface;

final readonly class AbortOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
