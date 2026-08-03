<?php

declare(strict_types=1);

namespace Demo\Catalog\Input;

use Symfony\Component\Console\Attribute\Option;

final class SeedProductsInput
{
    #[Option(description: 'How many of the demo catalog products to also delist, to exercise that state')]
    public int $delistedCount = 1;
}
