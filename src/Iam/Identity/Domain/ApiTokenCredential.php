<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRehashed;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Label;

#[Aggregate('iam.identity.api_token_credential')]
final class ApiTokenCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) ApiTokenCredentialId $id;
    public private(set) IdentityId $identityId;
    public private(set) Label $label;
    private bool $revoked;

    public static function issue(
        ApiTokenCredentialId $id,
        IdentityId $identityId,
        string $identifier,
        Label $label,
        #[\SensitiveParameter]
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
            label: $label->value,
            secretHash: $hasher->hash($plainSecret),
            issuedAt: $issuedAt->format(\DateTimeInterface::ATOM),
            expiresAt: $expiresAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        if ($this->revoked) {
            return;
        }

        $this->recordThat(new ApiTokenCredentialRevoked(
            id: $this->id->toString(),
            revokedAt: $revokedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function rehash(#[\SensitiveParameter] string $plainSecret, SecretHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new ApiTokenCredentialRehashed(
            id: $this->id->toString(),
            hash: $hasher->hash($plainSecret),
            rehashedAt: $rehashedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyIssued(ApiTokenCredentialIssued $event): void
    {
        $this->id = ApiTokenCredentialId::fromString($event->id);
        $this->identityId = IdentityId::fromString($event->identityId);
        $this->label = Label::fromString($event->label);
        $this->revoked = false;
    }

    #[Apply]
    private function applyRevoked(ApiTokenCredentialRevoked $event): void
    {
        $this->revoked = true;
    }

    #[Apply]
    private function applyRehashed(ApiTokenCredentialRehashed $event): void
    {
    }
}
