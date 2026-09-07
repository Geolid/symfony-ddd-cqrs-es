<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Finder\Buyer;

final readonly class BuyerResult
{
    public function __construct(
        public string $buyerId,
        public string $identityId,
    ) {
    }
}
