<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\RegisterPayer;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterPayer implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
