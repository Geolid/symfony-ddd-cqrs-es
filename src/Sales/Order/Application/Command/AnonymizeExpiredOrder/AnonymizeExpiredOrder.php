<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\AnonymizeExpiredOrder;

use Shared\Application\Command\CommandInterface;

final readonly class AnonymizeExpiredOrder implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
