<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\DisputeOrder;

use Shared\Application\Command\CommandInterface;

final readonly class DisputeOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
