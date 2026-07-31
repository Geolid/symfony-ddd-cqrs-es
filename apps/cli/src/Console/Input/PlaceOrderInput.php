<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Sales\Order\Application\Validation\ValidBuyerId;
use Sales\Order\Application\Validation\ValidMoney;
use Symfony\Component\Console\Attribute\Argument;

final class PlaceOrderInput
{
    #[Argument(description: 'The customer placing the order')]
    #[ValidBuyerId]
    public string $customerId;

    #[Argument(description: 'Order total, in cents')]
    #[ValidMoney]
    public int $totalAmountInCents;
}
