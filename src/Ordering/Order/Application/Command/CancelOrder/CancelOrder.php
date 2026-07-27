<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Command\CancelOrder;

use Shared\Application\Command\CommandInterface;

final readonly class CancelOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
