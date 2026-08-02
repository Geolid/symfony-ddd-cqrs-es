<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\ListProduct;

use Shared\Application\Command\CommandInterface;

final readonly class ListProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
    ) {
    }
}
