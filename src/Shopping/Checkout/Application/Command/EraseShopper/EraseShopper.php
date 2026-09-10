<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\EraseShopper;

use Shared\Application\Command\CommandInterface;

final readonly class EraseShopper implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
