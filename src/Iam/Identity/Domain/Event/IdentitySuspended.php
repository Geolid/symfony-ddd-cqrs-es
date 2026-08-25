<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('iam.identity.identity.suspended')]
final readonly class IdentitySuspended
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased'))]
        public string $reason,
        public string $suspendedAt,
    ) {
    }
}
