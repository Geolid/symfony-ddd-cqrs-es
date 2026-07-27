<?php

declare(strict_types=1);

namespace Shared\Domain\Event;

/**
 * Marker for events recorded by an Aggregate. Domain Events are sealed to their own BC — never
 * dispatched, never subscribed to from another BC. Cross-BC visibility goes through an
 * Integration Event instead.
 */
interface DomainEventInterface
{
}
