<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrollmentConfirmed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
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
    private string $encryptedSecret;
    private bool $confirmed;
    private bool $revoked;

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
     * @throws InvalidTotpCodeException
     */
    public function confirm(string $identityId, #[\SensitiveParameter] string $code, TotpCipherInterface $cipher, TotpVerifierInterface $verifier, \DateTimeImmutable $confirmedAt): void
    {
        if ($this->identityId !== $identityId) {
            throw TotpCredentialOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->confirmed) {
            return;
        }

        if (!$verifier->verify($cipher->decrypt($this->encryptedSecret), $code)) {
            throw InvalidTotpCodeException::forId($this->id);
        }

        $this->recordThat(new TotpCredentialEnrollmentConfirmed(
            id: $this->id,
            confirmedAt: $confirmedAt,
        ));
    }

    /**
     * @throws TotpCredentialOwnedByAnotherIdentityException
     */
    public function revoke(string $identityId, \DateTimeImmutable $revokedAt): void
    {
        if ($this->identityId !== $identityId) {
            throw TotpCredentialOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->revoked) {
            return;
        }

        $this->recordThat(new TotpCredentialRevoked(
            id: $this->id,
            revokedAt: $revokedAt,
        ));
    }

    #[Apply]
    private function applyEnrolled(TotpCredentialEnrolled $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->encryptedSecret = $event->encryptedSecret;
        $this->confirmed = false;
        $this->revoked = false;
    }

    #[Apply]
    private function applyEnrollmentConfirmed(TotpCredentialEnrollmentConfirmed $event): void
    {
        $this->confirmed = true;
    }

    #[Apply]
    private function applyRevoked(TotpCredentialRevoked $event): void
    {
        $this->revoked = true;
    }
}
