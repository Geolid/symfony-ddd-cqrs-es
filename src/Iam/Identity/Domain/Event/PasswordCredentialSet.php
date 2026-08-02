<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.identity.password_credential_set')]
final readonly class PasswordCredentialSet implements DomainEventInterface
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        #[PersonalData(fallback: 'erased@erased.invalid')]
        public string $login,
        public string $hash,
        public string $setAt,
    ) {
    }
}
