<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ReturnOrder;

use Shared\Application\Command\CommandInterface;

final readonly class ReturnOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
