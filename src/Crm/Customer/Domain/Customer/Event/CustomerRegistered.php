<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Event;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Domain\Customer\ValueObject\Email;
use Crm\Customer\Domain\Customer\ValueObject\Name;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;

#[Event('crm.customer.customer.registered')]
final readonly class CustomerRegistered
{
    public function __construct(
        #[DataSubjectId]
        public CustomerId $id,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('Erased'), Name::class, 'fromString'))]
        public Name $firstName,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('Erased'), Name::class, 'fromString'))]
        public Name $lastName,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('%s@erased.invalid'), Email::class, 'fromString'))]
        public Email $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
