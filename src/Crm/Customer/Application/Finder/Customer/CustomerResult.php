<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Finder\Customer;

use Shared\Application\ErasureStatus;

final readonly class CustomerResult
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public \DateTimeImmutable $registeredAt,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
