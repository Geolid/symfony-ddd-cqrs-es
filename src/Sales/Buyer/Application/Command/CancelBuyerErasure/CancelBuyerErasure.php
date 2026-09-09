<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\CancelBuyerErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelBuyerErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
