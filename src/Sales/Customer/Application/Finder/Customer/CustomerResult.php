<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

final readonly class CustomerResult
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
