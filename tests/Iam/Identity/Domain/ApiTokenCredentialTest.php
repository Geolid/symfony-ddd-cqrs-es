<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRehashed;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Label;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApiTokenCredentialTest extends AggregateRootTestCase
{
    private SecretHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new DummySecretHasher();
    }

    #[Test]
    public function itIssues(): void
    {
        $id = ApiTokenCredentialId::generate();
        $identityId = IdentityId::generate();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(fn () => ApiTokenCredential::issue($id, $identityId, 'key_abc123', Label::fromString('CI pipeline'), 'S3cr3t!', $this->hasher, $issuedAt, $expiresAt))
            ->then(new ApiTokenCredentialIssued(
                $id->toString(),
                $identityId->toString(),
                'key_abc123',
                'CI pipeline',
                $this->hasher->hash('S3cr3t!'),
                $issuedAt->format(\DateTimeInterface::ATOM),
                $expiresAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itRevokes(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', 'CI pipeline', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke($revokedAt))
            ->then(new ApiTokenCredentialRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotRevokeAnAlreadyRevoked(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', 'CI pipeline', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)),
                new ApiTokenCredentialRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itRehashes(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $rehashedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', 'CI pipeline', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (ApiTokenCredential $credential) => $credential->rehash('S3cr3t!', $this->hasher, $rehashedAt))
            ->then(new ApiTokenCredentialRehashed($id, $this->hasher->hash('S3cr3t!'), $rehashedAt->format(\DateTimeInterface::ATOM)));
    }

    protected function aggregateClass(): string
    {
        return ApiTokenCredential::class;
    }
}
