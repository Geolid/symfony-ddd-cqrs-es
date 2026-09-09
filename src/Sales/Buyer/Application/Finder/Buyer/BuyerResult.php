<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Finder\Buyer;

use Shared\Application\ErasureStatus;

final readonly class BuyerResult
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $registeredAt,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
