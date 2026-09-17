<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Pii\ErasedFieldSentinel;

#[Event('integration.iam.identity.identity.registered')]
final readonly class IdentityRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $fullName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('%s@erased.invalid'))]
        public string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
