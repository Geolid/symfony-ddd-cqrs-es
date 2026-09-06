<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;

#[Event('iam.identity.identity.reactivated')]
final readonly class IdentityReactivated
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('erased'), Reason::class, 'fromString'))]
        public Reason $reason,
        public \DateTimeImmutable $reactivatedAt,
    ) {
    }
}
