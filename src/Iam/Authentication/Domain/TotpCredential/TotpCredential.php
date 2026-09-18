<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialConfirmed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotConfirmableException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;

#[Aggregate('iam.authentication.totp_credential')]
final class TotpCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<TotpCredentialState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        TotpCredentialState::PENDING->value => [TotpCredentialState::CONFIRMED, TotpCredentialState::REVOKED],
        TotpCredentialState::CONFIRMED->value => [TotpCredentialState::REVOKED],
        TotpCredentialState::REVOKED->value => [],
    ];

    #[Id]
    public private(set) TotpCredentialId $id;
    private string $identityId;
    private string $encryptedSecret;
    private TotpCredentialState $operationalState;

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
     * @throws TotpCredentialNotConfirmableException
     * @throws InvalidTotpCodeException
     */
    public function confirm(string $identityId, #[\SensitiveParameter] string $code, TotpCipherInterface $cipher, TotpVerifierInterface $verifier, \DateTimeImmutable $confirmedAt): void
    {
        if ($this->identityId !== $identityId) {
            throw TotpCredentialOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->operationalState->isConfirmed()) {
            return;
        }

        if (!$this->canTransitionOperationalTo(TotpCredentialState::CONFIRMED)) {
            throw TotpCredentialNotConfirmableException::forId($this->id);
        }

        if (!$verifier->verify($cipher->decrypt($this->encryptedSecret), $code)) {
            throw InvalidTotpCodeException::forId($this->id);
        }

        $this->recordThat(new TotpCredentialConfirmed(
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

        if ($this->operationalState->isRevoked()) {
            return;
        }

        $this->recordThat(new TotpCredentialRevoked(
            id: $this->id,
            revokedAt: $revokedAt,
        ));
    }

    private function canTransitionOperationalTo(TotpCredentialState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    #[Apply]
    private function applyEnrolled(TotpCredentialEnrolled $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->encryptedSecret = $event->encryptedSecret;
        $this->operationalState = TotpCredentialState::PENDING;
    }

    #[Apply]
    private function applyConfirmed(TotpCredentialConfirmed $event): void
    {
        $this->operationalState = TotpCredentialState::CONFIRMED;
    }

    #[Apply]
    private function applyRevoked(TotpCredentialRevoked $event): void
    {
        $this->operationalState = TotpCredentialState::REVOKED;
    }
}
