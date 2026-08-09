<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IssueApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssuesAnApiTokenCredential(): void
    {
        // Given
        $identifier = 'key_'.bin2hex(random_bytes(4));

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: IdentityId::generate()->toString(),
            identifier: $identifier,
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertSame('CI pipeline', $result->label);
    }

    #[Test]
    public function itFailsWhenTheLabelIsAlreadyTakenForTheIdentity(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: 'key_'.bin2hex(random_bytes(4)),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: 'key_'.bin2hex(random_bytes(4)),
            secret: 'Another$3cr3t',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Test]
    public function itAcceptsTheSameLabelForADifferentIdentity(): void
    {
        // Given
        $otherIdentifier = 'key_'.bin2hex(random_bytes(4));
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: IdentityId::generate()->toString(),
            identifier: $otherIdentifier,
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        $identifier = 'key_'.bin2hex(random_bytes(4));

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: IdentityId::generate()->toString(),
            identifier: $identifier,
            secret: 'Another$3cr3t',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertSame('CI pipeline', $result->label);
    }
}
