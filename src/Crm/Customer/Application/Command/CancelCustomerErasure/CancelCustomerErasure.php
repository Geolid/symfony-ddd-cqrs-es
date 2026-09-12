<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\CancelCustomerErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelCustomerErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
