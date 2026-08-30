<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\ApiKey;

use Iam\Authentication\Application\ApiKey\ApiKeyIssuerInterface;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $apiKey = $this->issuer->issueFor($identity->id->toString(), 'CI pipeline');

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($apiKey->keyId);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('CI pipeline', $result->label);
        self::assertFalse($result->revoked);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
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
