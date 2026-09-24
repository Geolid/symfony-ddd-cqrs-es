<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialIssued;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
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
    private bool $revoked;

    public static function issue(
        TotpCredentialId $id,
        string $identityId,
        #[\SensitiveParameter]
        string $secret,
        TotpCipherInterface $cipher,
        \DateTimeImmutable $issuedAt,
    ): self {
        $self = new self();
        $self->recordThat(new TotpCredentialIssued(
            id: $id,
            identityId: $identityId,
            encryptedSecret: $cipher->encrypt($secret),
            issuedAt: $issuedAt,
        ));

        return $self;
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
    private function applyIssued(TotpCredentialIssued $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->revoked = false;
    }

    #[Apply]
    private function applyRevoked(TotpCredentialRevoked $event): void
    {
        $this->revoked = true;
    }
}
