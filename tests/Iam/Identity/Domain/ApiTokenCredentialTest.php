<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\ApiTokenCredentialId;
use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApiTokenCredentialTest extends AggregateRootTestCase
{
    #[Test]
    public function itIssuesAnApiTokenCredential(): void
    {
        $id = ApiTokenCredentialId::generate();
        $identityId = IdentityId::generate();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $hasher = new DummyApiTokenSecretHasher();

        $this
            ->given()
            ->when(static fn () => ApiTokenCredential::issue($id, $identityId, 'key_abc123', 'S3cr3t!', $hasher, $issuedAt, $expiresAt))
            ->then(new ApiTokenCredentialIssued(
                $id->toString(),
                $identityId->toString(),
                'key_abc123',
                $hasher->hash('S3cr3t!'),
                $issuedAt->format('c'),
                $expiresAt->format('c'),
            ));
    }

    #[Test]
    public function itRevokesAnApiTokenCredential(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $hasher = new DummyApiTokenSecretHasher();

        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $hasher->hash('S3cr3t!'), $issuedAt->format('c'), $expiresAt->format('c')))
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke($revokedAt))
            ->then(new ApiTokenCredentialRevoked($id, $revokedAt->format('c')));
    }

    #[Test]
    public function itCannotRevokeAnAlreadyRevokedApiTokenCredential(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $hasher = new DummyApiTokenSecretHasher();

        $this
            ->given(
                new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $hasher->hash('S3cr3t!'), $issuedAt->format('c'), $expiresAt->format('c')),
                new ApiTokenCredentialRevoked($id, $revokedAt->format('c')),
            )
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(ApiTokenCredentialAlreadyRevokedException::class);
    }

    #[Test]
    public function itVerifiesAnActiveUnexpiredToken(): void
    {
        // Given
        $hasher = new DummyApiTokenSecretHasher();
        $now = new \DateTimeImmutable('2026-06-01T00:00:00+00:00');
        $credential = ApiTokenCredential::issue(
            ApiTokenCredentialId::generate(),
            IdentityId::generate(),
            'key_abc123',
            'S3cr3t!',
            $hasher,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2027-01-01T00:00:00+00:00'),
        );

        // When
        $correctResult = $credential->verify('S3cr3t!', $hasher, $now);
        $wrongResult = $credential->verify('wrong', $hasher, $now);

        // Then
        self::assertTrue($correctResult);
        self::assertFalse($wrongResult);
    }

    #[Test]
    public function itRefusesAnExpiredToken(): void
    {
        // Given
        $hasher = new DummyApiTokenSecretHasher();
        $afterExpiry = new \DateTimeImmutable('2027-06-01T00:00:00+00:00');
        $credential = ApiTokenCredential::issue(
            ApiTokenCredentialId::generate(),
            IdentityId::generate(),
            'key_abc123',
            'S3cr3t!',
            $hasher,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2027-01-01T00:00:00+00:00'),
        );

        // When
        $result = $credential->verify('S3cr3t!', $hasher, $afterExpiry);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itRefusesARevokedToken(): void
    {
        // Given
        $hasher = new DummyApiTokenSecretHasher();
        $now = new \DateTimeImmutable('2026-06-01T00:00:00+00:00');
        $credential = ApiTokenCredential::issue(
            ApiTokenCredentialId::generate(),
            IdentityId::generate(),
            'key_abc123',
            'S3cr3t!',
            $hasher,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2027-01-01T00:00:00+00:00'),
        );
        $credential->revoke($now);

        // When
        $result = $credential->verify('S3cr3t!', $hasher, $now);

        // Then
        self::assertFalse($result);
    }

    protected function aggregateClass(): string
    {
        return ApiTokenCredential::class;
    }
}

final class DummyApiTokenSecretHasher implements SecretHasherInterface
{
    public function hash(string $secret): string
    {
        return 'hashed:'.$secret;
    }

    public function verify(string $hash, string $secret): bool
    {
        return $hash === $this->hash($secret);
    }
}
