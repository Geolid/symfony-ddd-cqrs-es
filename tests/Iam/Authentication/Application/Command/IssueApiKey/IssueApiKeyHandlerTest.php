<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\IssueApiKey;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\IssueApiKey\Exception\ApiKeyCredentialLabelAlreadyInUseException;
use Iam\Authentication\Application\Command\IssueApiKey\IssueApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IssueApiKeyHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssues(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $id = Uuid::uuid7()->toString();
        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $keyId = ApiKeyCredentialBuilder::sample('keyId')->value;
        $secret = ApiKeyCredentialBuilder::sample('secret');
        $now = Clock::get()->now();

        // When
        $this->dispatch(new IssueApiKey($id, $identityId, $label, $keyId, $secret));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($label, $result->label);
        self::assertSame($keyId, $result->keyId);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertFalse($result->revoked);
        self::assertNull($result->revokedAt);

        self::assertNotSame($secret, $result->secretHash);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyInUse(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');

        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(AuthenticationUniqueKey::API_KEY_CREDENTIAL_LABEL, $identityId),
            $label,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(ApiKeyCredentialLabelAlreadyInUseException::class);

        // When
        $this->dispatch(new IssueApiKey(
            Uuid::uuid7()->toString(),
            $identityId,
            $label,
            ApiKeyCredentialBuilder::sample('keyId')->value,
            ApiKeyCredentialBuilder::sample('secret'),
        ));
    }
}
