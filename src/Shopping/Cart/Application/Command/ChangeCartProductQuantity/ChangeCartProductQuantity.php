<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Command\ChangeCartProductQuantity;

use Shared\Application\Command\CommandInterface;

final readonly class ChangeCartProductQuantity implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $productId,
        public int $quantity,
    ) {
    }
}
