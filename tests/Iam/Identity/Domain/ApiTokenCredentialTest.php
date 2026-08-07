<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApiTokenCredentialTest extends AggregateRootTestCase
{
    private SecretHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new DummyApiTokenSecretHasher();
    }

    #[Test]
    public function itIssuesAnApiTokenCredential(): void
    {
        $id = ApiTokenCredentialId::generate();
        $identityId = IdentityId::generate();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(fn () => ApiTokenCredential::issue($id, $identityId, 'key_abc123', 'S3cr3t!', $this->hasher, $issuedAt, $expiresAt))
            ->then(new ApiTokenCredentialIssued(
                $id->toString(),
                $identityId->toString(),
                'key_abc123',
                $this->hasher->hash('S3cr3t!'),
                $issuedAt->format(\DateTimeInterface::ATOM),
                $expiresAt->format(\DateTimeInterface::ATOM),
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

        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke($revokedAt))
            ->then(new ApiTokenCredentialRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotRevokeAnAlreadyRevokedApiTokenCredential(): void
    {
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)),
                new ApiTokenCredentialRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (ApiTokenCredential $credential) => $credential->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(ApiTokenCredentialAlreadyRevokedException::class);
    }

    #[Test]
    public function itIsExpiredOnlyAfterTheExpiryInstant(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $atExpiry = null;
        $afterExpiry = null;

        // When
        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(static function (ApiTokenCredential $credential) use ($expiresAt, &$atExpiry, &$afterExpiry): void {
                $atExpiry = $credential->isExpired($expiresAt);
                $afterExpiry = $credential->isExpired($expiresAt->modify('+1 second'));
            })
            ->then();

        // Then
        self::assertFalse($atExpiry);
        self::assertTrue($afterExpiry);
    }

    #[Test]
    public function itVerifiesAnActiveUnexpiredToken(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-01T00:00:00+00:00');
        $correctResult = null;
        $wrongResult = null;

        // When
        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(function (ApiTokenCredential $credential) use ($now, &$correctResult, &$wrongResult): void {
                $correctResult = $credential->verify('S3cr3t!', $this->hasher, $now);
                $wrongResult = $credential->verify('wrong', $this->hasher, $now);
            })
            ->then();

        // Then
        self::assertTrue($correctResult);
        self::assertFalse($wrongResult);
    }

    #[Test]
    public function itRefusesAnExpiredToken(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $afterExpiry = new \DateTimeImmutable('2027-06-01T00:00:00+00:00');
        $result = null;

        // When
        $this
            ->given(new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)))
            ->when(function (ApiTokenCredential $credential) use ($afterExpiry, &$result): void {
                $result = $credential->verify('S3cr3t!', $this->hasher, $afterExpiry);
            })
            ->then();

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itRefusesARevokedToken(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $issuedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-01T00:00:00+00:00');
        $result = null;

        // When
        $this
            ->given(
                new ApiTokenCredentialIssued($id, $identityId, 'key_abc123', $this->hasher->hash('S3cr3t!'), $issuedAt->format(\DateTimeInterface::ATOM), $expiresAt->format(\DateTimeInterface::ATOM)),
                new ApiTokenCredentialRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(function (ApiTokenCredential $credential) use ($now, &$result): void {
                $result = $credential->verify('S3cr3t!', $this->hasher, $now);
            })
            ->then();

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
