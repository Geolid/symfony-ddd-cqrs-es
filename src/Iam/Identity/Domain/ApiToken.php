<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\ApiTokenIssued;
use Iam\Identity\Domain\Event\ApiTokenRevoked;
use Iam\Identity\Domain\Exception\ApiTokenAlreadyRevokedException;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.api_token')]
final class ApiToken implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ApiTokenId $id;
    private bool $revoked;

    public function id(): ApiTokenId
    {
        return $this->id;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public static function issue(
        ApiTokenId $id,
        IdentityId $identityId,
        string $identifier,
        string $secretHash,
        \DateTimeImmutable $issuedAt,
    ): self {
        $self = new self();
        $self->recordThat(new ApiTokenIssued(
            id: $id->toString(),
            identityId: $identityId->toString(),
            identifier: $identifier,
            secretHash: $secretHash,
            issuedAt: $issuedAt->format('c'),
        ));

        return $self;
    }

    /**
     * @throws ApiTokenAlreadyRevokedException
     */
    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        if ($this->revoked) {
            throw ApiTokenAlreadyRevokedException::forId($this->id);
        }

        $this->recordThat(new ApiTokenRevoked(
            id: $this->id->toString(),
            revokedAt: $revokedAt->format('c'),
        ));
    }

    #[Apply]
    private function applyApiTokenIssued(ApiTokenIssued $event): void
    {
        $this->id = ApiTokenId::fromString($event->id);
        $this->revoked = false;
    }

    #[Apply]
    private function applyApiTokenRevoked(ApiTokenRevoked $event): void
    {
        $this->revoked = true;
    }
}
