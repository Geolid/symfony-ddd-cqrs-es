<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential;

use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Label;

#[Aggregate('iam.authentication.api_key_credential')]
final class ApiKeyCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) ApiKeyCredentialId $id;
    public private(set) Label $label;
    private string $identityId;
    private bool $revoked;

    public static function issue(
        ApiKeyCredentialId $id,
        string $identityId,
        Label $label,
        KeyId $keyId,
        #[\SensitiveParameter]
        string $secret,
        ApiKeyHasherInterface $hasher,
        \DateTimeImmutable $issuedAt,
    ): self {
        $self = new self();
        $self->recordThat(new ApiKeyCredentialIssued(
            id: $id->toString(),
            identityId: $identityId,
            label: $label->value,
            keyId: $keyId->value,
            secretHash: $hasher->hash($secret),
            issuedAt: $issuedAt,
        ));

        return $self;
    }

    /**
     * @throws ApiKeyCredentialOwnedByAnotherIdentityException
     */
    public function revoke(string $identityId, \DateTimeImmutable $revokedAt): void
    {
        if ($this->identityId !== $identityId) {
            throw ApiKeyCredentialOwnedByAnotherIdentityException::forId($this->id);
        }

        if ($this->revoked) {
            return;
        }

        $this->recordThat(new ApiKeyCredentialRevoked(
            id: $this->id->toString(),
            revokedAt: $revokedAt,
        ));
    }

    #[Apply]
    private function applyIssued(ApiKeyCredentialIssued $event): void
    {
        $this->id = ApiKeyCredentialId::fromString($event->id);
        $this->identityId = $event->identityId;
        $this->label = Label::fromString($event->label);
        $this->revoked = false;
    }

    #[Apply]
    private function applyRevoked(ApiKeyCredentialRevoked $event): void
    {
        $this->revoked = true;
    }
}
