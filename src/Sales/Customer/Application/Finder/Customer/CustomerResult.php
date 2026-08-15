<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Shared\Application\Result\ResultInterface;

final readonly class CustomerResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public ?string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
