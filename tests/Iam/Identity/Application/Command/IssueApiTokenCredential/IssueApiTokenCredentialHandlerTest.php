<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenIdentifierTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IssueApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    use ApiTokenIdentifierTrait;

    private ApiTokenCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ApiTokenCredentialFinderInterface::class);
    }

    #[Test]
    public function itIssuesAnApiTokenCredential(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $identifier = $this->generateIdentifier();

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identity->id()->toString(),
            identifier: $identifier,
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $result = $this->finder->ofIdentifier($identifier);
        self::assertSame('CI pipeline', $result->label);
    }

    #[Test]
    public function itFailsWhenTheLabelIsAlreadyTakenForTheIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $identityId = $identity->id()->toString();
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: $this->generateIdentifier(),
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
            identifier: $this->generateIdentifier(),
            secret: 'Another$3cr3t',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Test]
    public function itAcceptsTheSameLabelForADifferentIdentity(): void
    {
        // Given
        $firstIdentity = IdentityTestFactory::new()->create();
        $this->store($firstIdentity);
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $firstIdentity->id()->toString(),
            identifier: $this->generateIdentifier(),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        $secondIdentity = IdentityTestFactory::new()->create();
        $this->store($secondIdentity);
        $identifier = $this->generateIdentifier();

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $secondIdentity->id()->toString(),
            identifier: $identifier,
            secret: 'Another$3cr3t',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $result = $this->finder->ofIdentifier($identifier);
        self::assertSame('CI pipeline', $result->label);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: IdentityId::generate()->toString(),
            identifier: $this->generateIdentifier(),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identity->id()->toString(),
            identifier: $this->generateIdentifier(),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identity->id()->toString(),
            identifier: $this->generateIdentifier(),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }
}
