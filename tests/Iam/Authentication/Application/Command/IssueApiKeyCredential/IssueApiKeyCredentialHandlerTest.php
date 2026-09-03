<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\IssueApiKeyCredential;

use Iam\Authentication\Application\Command\IssueApiKeyCredential\IssueApiKeyCredential;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IssueApiKeyCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssues(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $id = ApiKeyCredentialBuilder::sample('id')->toString();
        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $keyId = ApiKeyCredentialBuilder::sample('keyId')->value;
        $secret = ApiKeyCredentialBuilder::sample('secret');
        $now = Clock::get()->now();

        // When
        $this->dispatch(new IssueApiKeyCredential($id, $identityId, $label, $keyId, $secret));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($label, $result->label);
        self::assertSame($keyId, $result->keyId);
        self::assertSame(
            $now->format(\DateTimeImmutable::ATOM),
            $result->issuedAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertFalse($result->revoked);
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);

        self::assertNotSame($secret, $result->secretHash);
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
            ApiKeyCredentialBuilder::sample('id')->toString(),
        );

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->dispatch(new IssueApiKeyCredential(
            ApiKeyCredentialBuilder::sample('id')->toString(),
            $identityId,
            $label,
            ApiKeyCredentialBuilder::sample('keyId')->value,
            ApiKeyCredentialBuilder::sample('secret'),
        ));
    }
}
