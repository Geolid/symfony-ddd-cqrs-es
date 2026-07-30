<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Ordering\Order\Application\Validation\ValidMoney;
use Symfony\Component\Console\Attribute\Argument;

final class PlaceOrderInput
{
    #[Argument(description: 'The customer placing the order')]
    public string $customerId;

    #[Argument(description: 'Order total, in cents')]
    #[ValidMoney]
    public int $totalAmountInCents;
}
