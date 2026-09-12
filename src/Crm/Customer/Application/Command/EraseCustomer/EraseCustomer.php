<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\EraseCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class EraseCustomer implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
