<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;

final class ApiKeyCredentialTest extends AggregateRootTestCase
{
    #[Test]
    public function itIssues(): void
    {
        $id = ApiKeyCredentialId::generate();
        $identityId = Uuid::uuid7()->toString();
        $keyId = KeyId::fromString(KeyId::PREFIX.'0123456789abcdef');
        $hasher = new StubApiKeyHasher();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): ApiKeyCredential => ApiKeyCredential::issue(
                $id,
                $identityId,
                Label::fromString('CI pipeline'),
                $keyId,
                'plain-secret',
                $hasher,
                $now,
            ))
            ->then(new ApiKeyCredentialIssued(
                $id->toString(),
                $identityId,
                'CI pipeline',
                $keyId->value,
                $hasher->hash('plain-secret'),
                $now,
            ));
    }

    #[Test]
    public function itRevokes(): void
    {
        $id = ApiKeyCredentialId::generate();
        $identityId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = $now->modify('+1 day');

        $this
            ->given(new ApiKeyCredentialIssued(
                $id->toString(),
                $identityId,
                'CI pipeline',
                KeyId::PREFIX.'0123456789abcdef',
                'hashed:plain-secret',
                $now,
            ))
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke($identityId, $revokedAt))
            ->then(new ApiKeyCredentialRevoked($id->toString(), $revokedAt));
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $id = ApiKeyCredentialId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new ApiKeyCredentialIssued(
                $id->toString(),
                Uuid::uuid7()->toString(),
                'CI pipeline',
                KeyId::PREFIX.'0123456789abcdef',
                'hashed:plain-secret',
                $now,
            ))
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke(Uuid::uuid7()->toString(), $now->modify('+1 day')))
            ->expectsException(ApiKeyCredentialOwnedByAnotherIdentityException::class);
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $id = ApiKeyCredentialId::generate();
        $identityId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new ApiKeyCredentialIssued(
                    $id->toString(),
                    $identityId,
                    'CI pipeline',
                    KeyId::PREFIX.'0123456789abcdef',
                    'hashed:plain-secret',
                    $now,
                ),
                new ApiKeyCredentialRevoked($id->toString(), $now->modify('+1 day')),
            )
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke($identityId, $now->modify('+2 days')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return ApiKeyCredential::class;
    }
}
