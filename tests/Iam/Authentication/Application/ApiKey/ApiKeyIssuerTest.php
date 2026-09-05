<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\ApiKey;

use Iam\Authentication\Application\ApiKey\ApiKeyIssuerInterface;
use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialLabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $label = ApiKeyCredentialBuilder::sample('label')->value;

        // When
        $apiKey = $this->issuer->issueFor($identityId, $label);

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($apiKey->keyId);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($label, $result->label);
        self::assertFalse($result->revoked);

        self::assertNotSame($apiKey->secret, $result->secretHash);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');

        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId),
            $label,
            ApiKeyCredentialId::generate()->toString(),
        );

        // Then
        $this->expectException(ApiKeyCredentialLabelAlreadyTakenException::class);

        // When
        $this->issuer->issueFor($identityId, $label);
    }
}
