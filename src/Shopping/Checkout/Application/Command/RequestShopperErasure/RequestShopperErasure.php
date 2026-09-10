<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RequestShopperErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestShopperErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
