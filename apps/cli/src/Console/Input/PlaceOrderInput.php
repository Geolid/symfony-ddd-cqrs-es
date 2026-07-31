<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Sales\Customer\Application\Validation\ValidCustomerId;
use Sales\Order\Application\Validation\ValidMoney;
use Symfony\Component\Console\Attribute\Argument;

final class PlaceOrderInput
{
    #[Argument(description: 'The customer placing the order')]
    #[ValidCustomerId]
    public string $customerId;

    #[Argument(description: 'Order total, in cents')]
    #[ValidMoney]
    public int $totalAmountInCents;
}
