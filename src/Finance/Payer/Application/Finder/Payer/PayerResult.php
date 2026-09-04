<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Finder\Payer;

final readonly class PayerResult
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
