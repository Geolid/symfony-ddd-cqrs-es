<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\FailOrder;

use Shared\Application\Command\CommandInterface;

final readonly class FailOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
