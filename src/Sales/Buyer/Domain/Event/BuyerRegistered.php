<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;

#[Event('sales.buyer.buyer.registered')]
final readonly class BuyerRegistered
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('%s@erased.invalid'), Email::class, 'fromString'))]
        public Email $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
