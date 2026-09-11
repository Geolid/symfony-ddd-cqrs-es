<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ExpireCheckoutSession;

use Shared\Application\Command\CommandInterface;

final readonly class ExpireCheckoutSession implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
