<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Credential;

use Iam\Identity\Application\Credential\ApiTokenIssuer;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Security\ApiTokenGeneratorInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueValue;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Label;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ApiTokenIssuerTest extends AbstractIntegrationTestCase
{
    private ApiTokenCredentialFinderInterface $finder;

    private ApiTokenIssuer $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ApiTokenCredentialFinderInterface::class);
        $this->service = new ApiTokenIssuer(
            $this->service(ApiTokenGeneratorInterface::class),
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itIssuesAnApiTokenCredentialAndReturnsTheGeneratedKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $expiresAt = new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM);

        // When
        $apiKey = $this->service->issueFor($identity->id()->toString(), 'CI pipeline', $expiresAt);

        // Then
        self::assertNotEmpty($apiKey->identifier);
        self::assertNotEmpty($apiKey->secret);

        $result = $this->finder->ofIdentifier($apiKey->identifier);
        self::assertSame('CI pipeline', $result->label);
        self::assertSame($expiresAt, $result->expiresAt->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->service->issueFor(IdentityId::generate()->toString(), 'CI pipeline', new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->service->issueFor($identity->id()->toString(), 'CI pipeline', new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itFailsWhenTheLabelIsAlreadyTakenForTheIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $identityId = $identity->id()->toString();
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            ApiTokenCredentialUniqueValue::LABEL,
            Label::fromString('CI pipeline')->fingerprintFor($identityId),
        );

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->service->issueFor($identityId, 'CI pipeline', new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM));
    }
}
