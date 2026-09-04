<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\PublishProduct;

use Shared\Application\Command\CommandInterface;

final readonly class PublishProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitPriceInCents,
    ) {
    }
}
