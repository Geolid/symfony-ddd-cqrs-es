<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\ApiKey;

use Iam\Authentication\Application\ApiKey\ApiKeyIssuerInterface;
use Iam\Authentication\Application\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Application\Command\IssueApiKeyCredential\Exception\ApiKeyCredentialLabelAlreadyInUseException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
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
    public function itFailsWhenLabelAlreadyInUse(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');

        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId),
            $label,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(ApiKeyCredentialLabelAlreadyInUseException::class);

        // When
        $this->issuer->issueFor($identityId, $label);
    }
}
