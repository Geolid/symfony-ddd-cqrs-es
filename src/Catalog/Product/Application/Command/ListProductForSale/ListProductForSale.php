<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Command\ListProductForSale;

use Shared\Application\Command\CommandInterface;

final readonly class ListProductForSale implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
    ) {
    }
}
