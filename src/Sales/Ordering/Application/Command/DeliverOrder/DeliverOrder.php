<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\DeliverOrder;

use Shared\Application\Command\CommandInterface;

final readonly class DeliverOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
