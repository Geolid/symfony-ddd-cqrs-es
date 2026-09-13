<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Event;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('crm.customer.customer.billing_address_defined')]
final readonly class CustomerBillingAddressDefined
{
    public function __construct(
        #[DataSubjectId]
        public CustomerId $id,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']), Address::class, 'of'),
            ]),
            PostalAddress::class,
            'of',
        ))]
        public PostalAddress $postalAddress,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
