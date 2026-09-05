<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PrepareOrder;

use Shared\Application\Command\CommandInterface;

final readonly class PrepareOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
