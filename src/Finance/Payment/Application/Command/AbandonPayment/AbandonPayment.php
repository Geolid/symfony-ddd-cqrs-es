<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\AbandonPayment;

use Shared\Application\Command\CommandInterface;

final readonly class AbandonPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
