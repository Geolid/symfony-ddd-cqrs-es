<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RemoveCartProduct;

use Shared\Application\Command\CommandInterface;

final readonly class RemoveCartProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $productId,
    ) {
    }
}
