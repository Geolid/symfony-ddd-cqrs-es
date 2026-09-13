<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\RequestCustomerErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestCustomerErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
