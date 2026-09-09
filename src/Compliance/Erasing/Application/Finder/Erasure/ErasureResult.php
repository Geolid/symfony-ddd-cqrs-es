<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Finder\Erasure;

use Compliance\Erasing\Application\ErasureRequestStatus;

final readonly class ErasureResult
{
    public function __construct(
        public string $id,
        public ErasureRequestStatus $status,
        public ?\DateTimeImmutable $requestedAt,
    ) {
    }
}
