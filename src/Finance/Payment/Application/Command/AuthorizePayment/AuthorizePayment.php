<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\AuthorizePayment;

use Shared\Application\Command\CommandInterface;

final readonly class AuthorizePayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
