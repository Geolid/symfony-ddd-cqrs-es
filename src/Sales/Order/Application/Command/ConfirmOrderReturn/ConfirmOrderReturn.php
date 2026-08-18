<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ConfirmOrderReturn;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmOrderReturn implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
