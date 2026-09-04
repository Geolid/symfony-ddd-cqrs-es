<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\RepriceProduct;

use Shared\Application\Command\CommandInterface;

final readonly class RepriceProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public int $unitPriceInCents,
    ) {
    }
}
