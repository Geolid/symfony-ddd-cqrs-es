<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RemoveCartLine;

use Shared\Application\Command\CommandInterface;

final readonly class RemoveCartLine implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $lineId,
    ) {
    }
}
