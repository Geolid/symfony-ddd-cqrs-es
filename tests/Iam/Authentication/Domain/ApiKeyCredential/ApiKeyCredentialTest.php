<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
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
        $keyId = KeyId::fromString(KeyId::PREFIX.bin2hex(random_bytes(8)));
        $hasher = new StubApiKeyHasher();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): ApiKeyCredential => ApiKeyCredential::issue(
                $id,
                $identityId,
                Label::fromString('CI pipeline'),
                $keyId,
                'plain-secret',
                $hasher,
                $issuedAt,
            ))
            ->then(new ApiKeyCredentialIssued(
                $id->toString(),
                $identityId,
                'CI pipeline',
                $keyId->value,
                $hasher->hash('plain-secret'),
                $issuedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itRevokes(): void
    {
        $id = ApiKeyCredentialId::generate();
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ApiKeyCredentialIssued(
                $id->toString(),
                Uuid::uuid7()->toString(),
                'CI pipeline',
                KeyId::PREFIX.bin2hex(random_bytes(8)),
                'hashed:plain-secret',
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke($revokedAt))
            ->then(new ApiKeyCredentialRevoked($id->toString(), $revokedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotRevokeAlreadyRevoked(): void
    {
        $id = ApiKeyCredentialId::generate();

        $this
            ->given(
                new ApiKeyCredentialIssued(
                    $id->toString(),
                    Uuid::uuid7()->toString(),
                    'CI pipeline',
                    KeyId::PREFIX.bin2hex(random_bytes(8)),
                    'hashed:plain-secret',
                    '2026-01-01T00:00:00+00:00',
                ),
                new ApiKeyCredentialRevoked($id->toString(), '2026-01-02T00:00:00+00:00'),
            )
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return ApiKeyCredential::class;
    }
}
