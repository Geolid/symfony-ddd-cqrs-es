<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\PurchaseCart;

use Shared\Application\Command\CommandInterface;

final readonly class PurchaseCart implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
