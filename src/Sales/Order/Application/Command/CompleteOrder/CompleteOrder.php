<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CompleteOrder;

use Shared\Application\Command\CommandInterface;

final readonly class CompleteOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
