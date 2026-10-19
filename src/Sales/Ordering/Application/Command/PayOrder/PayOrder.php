<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\PayOrder;

use Shared\Application\Command\CommandInterface;

final readonly class PayOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
