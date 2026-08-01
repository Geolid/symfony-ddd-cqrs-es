<?php

declare(strict_types=1);

namespace Demo\Sales\Input;

use Symfony\Component\Console\Attribute\Option;

final class SeedCustomersInput
{
    #[Option(description: 'How many customers to seed')]
    public int $count = 5;

    #[Option(description: 'Domain the seeded addresses belong to')]
    public string $domain = 'example.invalid';
}
