<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\Pii\ErasedEmail;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;

#[Event('iam.identity.identity.email_changed')]
final readonly class IdentityEmailChanged
{
    public function __construct(
        #[DataSubjectId]
        public IdentityId $id,
        #[SensitiveData(fallbackCallable: new ErasedEmail())]
        public Email $email,
        public \DateTimeImmutable $changedAt,
    ) {
    }
}
