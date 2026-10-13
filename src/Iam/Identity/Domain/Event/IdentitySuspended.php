<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\Pii\ErasedReason;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;

#[Event('iam.identity.identity.suspended')]
final readonly class IdentitySuspended
{
    public function __construct(
        #[DataSubjectId]
        public IdentityId $id,
        #[SensitiveData(fallbackCallable: new ErasedReason())]
        public Reason $reason,
        public \DateTimeImmutable $suspendedAt,
    ) {
    }
}
