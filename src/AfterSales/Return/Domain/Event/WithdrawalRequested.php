<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('after_sales.return.withdrawal.requested')]
final readonly class WithdrawalRequested
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $shippingAddress
     */
    public function __construct(
        public string $id,
        public string $orderId,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $shippingAddress,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
