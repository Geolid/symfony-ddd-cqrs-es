<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\DispatchOrder;

use Shared\Application\Command\CommandInterface;

final readonly class DispatchOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
