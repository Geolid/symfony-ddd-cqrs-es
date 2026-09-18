<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Event;

use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.totp_credential.enrollment_confirmed')]
final readonly class TotpCredentialEnrollmentConfirmed
{
    public function __construct(
        public TotpCredentialId $id,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
