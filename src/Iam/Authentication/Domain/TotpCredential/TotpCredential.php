<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialUnenrolled;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.totp_credential')]
final class TotpCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) TotpCredentialId $id;
    private string $identityId;
    private bool $unenrolled;

    public static function enroll(
        TotpCredentialId $id,
        string $identityId,
        #[\SensitiveParameter]
        string $secret,
        TotpCipherInterface $cipher,
        \DateTimeImmutable $enrolledAt,
    ): self {
        $self = new self();
        $self->recordThat(new TotpCredentialEnrolled(
            id: $id,
            identityId: $identityId,
            encryptedSecret: $cipher->encrypt($secret),
            enrolledAt: $enrolledAt,
        ));

        return $self;
    }

    /**
     * @throws TotpCredentialOwnedByAnotherIdentityException
     */
    public function unenroll(string $identityId, \DateTimeImmutable $unenrolledAt): void
    {
        if ($this->identityId !== $identityId) {
            throw TotpCredentialOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->unenrolled) {
            return;
        }

        $this->recordThat(new TotpCredentialUnenrolled(
            id: $this->id,
            unenrolledAt: $unenrolledAt,
        ));
    }

    #[Apply]
    private function applyEnrolled(TotpCredentialEnrolled $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->unenrolled = false;
    }

    #[Apply]
    private function applyUnenrolled(TotpCredentialUnenrolled $event): void
    {
        $this->unenrolled = true;
    }
}
