<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Monitoring\Sentry;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface SentryContextProviderInterface
{
    public function name(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function provide(): ?array;
}
