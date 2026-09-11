<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\StaleCheckoutSession;

use Shared\Application\Command\CommandInterface;

final readonly class StaleCheckoutSession implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
