<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;
use Shopping\Checkout\Domain\ValueObject\Email;

#[Event('shopping.checkout.shopper.registered')]
final readonly class ShopperRegistered
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('%s@erased.invalid'), Email::class, 'fromString'))]
        public Email $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
