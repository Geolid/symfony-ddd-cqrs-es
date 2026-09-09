<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RequestBuyerErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestBuyerErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
