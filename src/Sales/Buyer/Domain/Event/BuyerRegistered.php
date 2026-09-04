<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.buyer.buyer.registered')]
final readonly class BuyerRegistered
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('%s@erased.invalid'))]
        public string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
