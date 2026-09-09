<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
