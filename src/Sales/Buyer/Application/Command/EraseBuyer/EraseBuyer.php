<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\EraseBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class EraseBuyer implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
