<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class CheckoutSessionId implements AggregateRootId
{
    use UuidTrait;
}
