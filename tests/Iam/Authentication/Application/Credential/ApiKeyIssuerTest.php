<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\ApiKeyIssuerInterface;
use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class ApiKeyIssuerTest extends AbstractIntegrationTestCase
{
    private ApiKeyIssuerInterface $issuer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->issuer = $this->service(ApiKeyIssuerInterface::class);
    }

    #[Test]
    public function itIssues(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $apiKey = $this->issuer->issueFor($identity->id->toString(), 'CI pipeline');

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($apiKey->keyId);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('CI pipeline', $result->label);
        self::assertFalse($result->revoked);
    }

    #[Test]
    public function itFailsWhenIdentityNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(AuthenticatableIdentityResultNotFoundException::class);

        // When
        $this->issuer->issueFor($identityId, 'CI pipeline');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->issuer->issueFor($identity->id->toString(), 'CI pipeline');
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identity->id->toString()),
            'CI pipeline',
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->issuer->issueFor($identity->id->toString(), 'CI pipeline');
    }
}
