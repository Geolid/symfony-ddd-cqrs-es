<?php

declare(strict_types=1);

namespace Demo\Sales\Input;

use Symfony\Component\Console\Attribute\Option;

final class SeedOrdersInput
{
    #[Option(description: 'Default customer ID prefix')]
    public string $customer = 'customer';

    #[Option(description: 'How many orders to seed')]
    public int $count = 20;

    #[Option(description: 'Weight for orders left placed')]
    public int $placedWeight = 70;

    #[Option(description: 'Weight for orders that get cancelled right after')]
    public int $cancelledWeight = 30;
}
