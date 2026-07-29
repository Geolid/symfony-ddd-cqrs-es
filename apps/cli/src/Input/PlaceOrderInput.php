<?php

declare(strict_types=1);

namespace Cli\Input;

use Ordering\Order\Infrastructure\Validation\ValidMoney;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderInput
{
    #[Argument(description: 'The customer placing the order')]
    #[Assert\NotBlank]
    public string $customerId;

    #[Argument(description: 'Order total, in cents')]
    #[ValidMoney]
    public int $totalAmountInCents;
}
