<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('integration.sales.buyer.buyer.registered')]
final readonly class BuyerRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('%s@erased.invalid'))]
        public string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
