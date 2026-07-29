<?php

declare(strict_types=1);

namespace Cli\Input;

use Ordering\Order\Infrastructure\Validation\ValidMoney;
use Symfony\Component\Console\Attribute\Argument;

final class PlaceOrderInput
{
    #[Argument(description: 'The customer placing the order')]
    public string $customerId;

    #[Argument(description: 'Order total, in cents')]
    #[ValidMoney]
    public int $totalAmountInCents;
}
