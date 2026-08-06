<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\EventStore;

final class IntegrationStreamId
{
    public static function build(string $bcPrefix, string $id): string
    {
        return \sprintf('%s.integration.%s', $bcPrefix, $id);
    }
}
