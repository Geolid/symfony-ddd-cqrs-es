<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Query\GetPendingErasureByIdentity;

final readonly class PendingErasureResult
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $requestedAt,
        public \DateTimeImmutable $effectiveAt,
    ) {
    }
}
