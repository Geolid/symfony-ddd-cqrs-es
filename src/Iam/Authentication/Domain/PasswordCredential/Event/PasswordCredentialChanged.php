<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.authentication.password_credential.changed')]
final readonly class PasswordCredentialChanged implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $hash,
        public string $changedAt,
    ) {
    }
}
