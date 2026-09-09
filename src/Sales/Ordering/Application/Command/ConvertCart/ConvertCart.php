<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConvertCart;

use Shared\Application\Command\CommandInterface;

final readonly class ConvertCart implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
