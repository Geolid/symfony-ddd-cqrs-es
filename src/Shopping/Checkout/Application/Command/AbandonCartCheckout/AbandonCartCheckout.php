<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AbandonCartCheckout;

use Shared\Application\Command\CommandInterface;

final readonly class AbandonCartCheckout implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
