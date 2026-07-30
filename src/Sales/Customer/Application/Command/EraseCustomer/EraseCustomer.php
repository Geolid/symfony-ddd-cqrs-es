<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\EraseCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class EraseCustomer implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
