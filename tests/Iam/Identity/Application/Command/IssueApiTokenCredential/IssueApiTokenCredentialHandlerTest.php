<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueKey;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenTrait;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class IssueApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    use ApiTokenTrait;

    private ApiTokenCredentialFinderInterface $finder;

    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ApiTokenCredentialFinderInterface::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itIssuesAnApiTokenCredential(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
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
        $identity = IdentityTestFactory::new()->store();
        $identityId = $identity->id()->toString();
        $this->uniqueValues->reserve(
            UniqueKey::for(ApiTokenCredentialUniqueKey::LABEL, $identityId),
            'CI pipeline',
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: $this->generateIdentifier(),
            secret: 'NewS3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Test]
    public function itAcceptsTheSameLabelForADifferentIdentity(): void
    {
        // Given
        $firstIdentity = IdentityTestFactory::new()->store();
        $this->uniqueValues->reserve(
            UniqueKey::for(ApiTokenCredentialUniqueKey::LABEL, $firstIdentity->id()->toString()),
            'CI pipeline',
            Uuid::uuid7()->toString(),
        );

        $secondIdentity = IdentityTestFactory::new()->store();
        $identifier = $this->generateIdentifier();

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $secondIdentity->id()->toString(),
            identifier: $identifier,
            secret: 'NewS3cr3t!',
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
        $identity = IdentityTestFactory::new()->suspended()->store();

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
        $identity = IdentityTestFactory::new()->erased()->store();

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
