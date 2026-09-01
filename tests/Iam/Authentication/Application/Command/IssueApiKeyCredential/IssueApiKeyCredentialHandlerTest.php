<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\IssueApiKeyCredential;

use Iam\Authentication\Application\Command\IssueApiKeyCredential\IssueApiKeyCredential;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class IssueApiKeyCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssues(): void
    {
        // Given
        $identityId = ApiKeyCredentialTestFactory::sample('identityId');
        $id = ApiKeyCredentialTestFactory::sample('id')->toString();
        $label = ApiKeyCredentialTestFactory::sample('label')->value;
        $keyId = ApiKeyCredentialTestFactory::sample('keyId')->value;
        $secret = ApiKeyCredentialTestFactory::sample('secret');

        // When
        $this->dispatch(new IssueApiKeyCredential($id, $identityId, $label, $keyId, $secret));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($label, $result->label);
        self::assertFalse($result->revoked);
        self::assertTrue($result->identityAuthenticatable);

        self::assertNotSame($secret, $result->secretHash);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $identityId = ApiKeyCredentialTestFactory::sample('identityId');

        $label = ApiKeyCredentialTestFactory::sample('label')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId),
            $label,
            ApiKeyCredentialTestFactory::sample('id')->toString(),
        );

        // Then
        $this->expectException(LabelAlreadyTakenException::class);

        // When
        $this->dispatch(new IssueApiKeyCredential(
            ApiKeyCredentialTestFactory::sample('id')->toString(),
            $identityId,
            $label,
            ApiKeyCredentialTestFactory::sample('keyId')->value,
            ApiKeyCredentialTestFactory::sample('secret'),
        ));
    }
}
