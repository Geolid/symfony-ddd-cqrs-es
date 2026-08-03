<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Security\IssueApiTokenCredentialInterface;
use Iam\Identity\Domain\IdentityId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialIssuingServiceTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssuesAnApiTokenCredentialForTheIdentity(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $expiresAt = new \DateTimeImmutable('+30 days +00:00');

        // When
        $apiKey = $this->service(IssueApiTokenCredentialInterface::class)->issue($identityId, $expiresAt);

        // Then
        self::assertMatchesRegularExpression('/^key_[0-9a-f]{16}$/', $apiKey->identifier);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $apiKey->secret);

        $credential = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($apiKey->identifier);
        self::assertNotNull($credential);
        self::assertSame($identityId, $credential->identityId);
        self::assertSame($expiresAt->format('c'), $credential->expiresAt->format('c'));
    }

    #[Test]
    public function itIssuesAUniqueIdentifierAndSecretEachTime(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $expiresAt = new \DateTimeImmutable('+30 days +00:00');
        $issuer = $this->service(IssueApiTokenCredentialInterface::class);

        // When
        $first = $issuer->issue($identityId, $expiresAt);
        $second = $issuer->issue($identityId, $expiresAt);

        // Then
        self::assertNotSame($first->identifier, $second->identifier);
        self::assertNotSame($first->secret, $second->secret);
    }
}
