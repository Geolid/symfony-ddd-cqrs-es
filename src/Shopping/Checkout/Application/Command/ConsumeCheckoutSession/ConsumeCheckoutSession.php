<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ConsumeCheckoutSession;

use Shared\Application\Command\CommandInterface;

final readonly class ConsumeCheckoutSession implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
