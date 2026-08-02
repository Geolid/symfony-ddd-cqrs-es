<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\ApiToken;
use Iam\Identity\Domain\ApiTokenId;
use Iam\Identity\Domain\Event\ApiTokenIssued;
use Iam\Identity\Domain\Event\ApiTokenRevoked;
use Iam\Identity\Domain\IdentityId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApiTokenTest extends AggregateRootTestCase
{
    #[Test]
    public function itIssuesAnApiToken(): void
    {
        $id = ApiTokenId::generate();
        $identityId = IdentityId::generate();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => ApiToken::issue($id, $identityId, 'key_abc123', 'hashed-secret', $issuedAt))
            ->then(new ApiTokenIssued($id->toString(), $identityId->toString(), 'key_abc123', 'hashed-secret', $issuedAt->format('c')));
    }

    #[Test]
    public function itRevokesAnApiToken(): void
    {
        $id = ApiTokenId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ApiTokenIssued($id, $identityId, 'key_abc123', 'hashed-secret', $issuedAt->format('c')))
            ->when(static fn (ApiToken $apiToken) => $apiToken->revoke($revokedAt))
            ->then(new ApiTokenRevoked($id, $revokedAt->format('c')));
    }

    #[Test]
    public function itCannotRevokeAnAlreadyRevokedApiToken(): void
    {
        $id = ApiTokenId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ApiTokenIssued($id, $identityId, 'key_abc123', 'hashed-secret', $issuedAt->format('c')),
                new ApiTokenRevoked($id, $revokedAt->format('c')),
            )
            ->when(static fn (ApiToken $apiToken) => $apiToken->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(\Iam\Identity\Domain\Exception\ApiTokenAlreadyRevokedException::class);
    }

    protected function aggregateClass(): string
    {
        return ApiToken::class;
    }
}
