<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ChangeCartLineQuantity;

use Shared\Application\Command\CommandInterface;

final readonly class ChangeCartLineQuantity implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $lineId,
        public int $quantity,
    ) {
    }
}
