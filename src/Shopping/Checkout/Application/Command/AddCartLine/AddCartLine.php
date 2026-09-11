<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AddCartLine;

use Shared\Application\Command\CommandInterface;

final readonly class AddCartLine implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $productId,
        public int $quantity,
    ) {
    }
}
