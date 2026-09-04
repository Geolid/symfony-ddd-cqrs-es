<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\CapturePayment;

use Shared\Application\Command\CommandInterface;

final readonly class CapturePayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
