<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRehashed;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Label;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.api_token_credential')]
final class ApiTokenCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ApiTokenCredentialId $id;
    private bool $revoked;

    public function id(): ApiTokenCredentialId
    {
        return $this->id;
    }

    public static function issue(
        ApiTokenCredentialId $id,
        IdentityId $identityId,
        string $identifier,
        Label $label,
        string $plainSecret,
        SecretHasherInterface $hasher,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): self {
        $self = new self();
        $self->recordThat(new ApiTokenCredentialIssued(
            id: $id->toString(),
            identityId: $identityId->toString(),
            identifier: $identifier,
            label: $label->toString(),
            secretHash: $hasher->hash($plainSecret),
            issuedAt: $issuedAt->format(\DateTimeInterface::ATOM),
            expiresAt: $expiresAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    /**
     * @throws ApiTokenCredentialAlreadyRevokedException
     */
    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        if ($this->revoked) {
            throw ApiTokenCredentialAlreadyRevokedException::forId($this->id);
        }

        $this->recordThat(new ApiTokenCredentialRevoked(
            id: $this->id->toString(),
            revokedAt: $revokedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function rehash(string $plainSecret, SecretHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new ApiTokenCredentialRehashed(
            id: $this->id->toString(),
            hash: $hasher->hash($plainSecret),
            rehashedAt: $rehashedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyApiTokenCredentialIssued(ApiTokenCredentialIssued $event): void
    {
        $this->id = ApiTokenCredentialId::fromString($event->id);
        $this->revoked = false;
    }

    #[Apply]
    private function applyApiTokenCredentialRevoked(ApiTokenCredentialRevoked $event): void
    {
        $this->revoked = true;
    }

    #[Apply]
    private function applyApiTokenCredentialRehashed(ApiTokenCredentialRehashed $event): void
    {
    }
}
