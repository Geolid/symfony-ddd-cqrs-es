<?php

declare(strict_types=1);

namespace Shared\Application\Event;

/**
 * Marks a message as the ONE public, cross-Bounded-Context contract a BC exposes for its
 * Domain Events. Domain Events themselves never leave their BC — an Infrastructure-layer
 * Translator converts them into Integration Events instead.
 */
interface IntegrationEventInterface
{
}
