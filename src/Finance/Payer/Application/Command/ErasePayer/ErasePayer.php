<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\ErasePayer;

use Shared\Application\Command\CommandInterface;

final readonly class ErasePayer implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
