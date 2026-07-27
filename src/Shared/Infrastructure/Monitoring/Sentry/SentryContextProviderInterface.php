<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Monitoring\Sentry;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Lets a Bounded Context enrich error-tracking events with its own context (e.g. "which
 * aggregate was being handled") without Shared\Infrastructure depending on that BC — the
 * dependency is inverted via this tagged interface instead (see ADR-003).
 */
#[AutoconfigureTag]
interface SentryContextProviderInterface
{
    public function name(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function provide(): ?array;
}
