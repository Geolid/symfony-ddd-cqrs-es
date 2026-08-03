<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CaptureOrderPayment;

use Shared\Application\Command\CommandInterface;

final readonly class CaptureOrderPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
