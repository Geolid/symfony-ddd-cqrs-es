<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\Pii\ErasedFullName;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;

#[Event('iam.identity.identity.full_name_changed')]
final readonly class IdentityFullNameChanged
{
    public function __construct(
        #[DataSubjectId]
        public IdentityId $id,
        #[SensitiveData(fallbackCallable: new ErasedFullName())]
        public FullName $fullName,
        public \DateTimeImmutable $changedAt,
    ) {
    }
}
