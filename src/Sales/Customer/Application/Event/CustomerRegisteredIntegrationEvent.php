<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\Event\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.customer.integration.registered')]
final readonly class CustomerRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-email-%s@customer.invalid'))]
        public string $email,
        public string $registeredAt,
    ) {
    }
}
