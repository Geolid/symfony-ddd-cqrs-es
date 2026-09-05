<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedPostalAddressSentinel;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('after_sales.return.withdrawal.requested')]
final readonly class WithdrawalRequested
{
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $shippingAddress,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
