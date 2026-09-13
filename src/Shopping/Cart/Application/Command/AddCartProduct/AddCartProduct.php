<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\AddCartProduct;

use Shared\Application\Command\CommandInterface;

final readonly class AddCartProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $productId,
        public int $quantity,
    ) {
    }
}
