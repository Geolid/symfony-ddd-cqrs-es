<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\CancelShopperErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelShopperErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
