<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\IssueApiKeyCredential;

use Iam\Authentication\Application\Command\IssueApiKeyCredential\IssueApiKeyCredential;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class IssueApiKeyCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssues(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $id = Uuid::uuid7()->toString();
        $keyId = KeyId::PREFIX.'0123456789abcdef';

        // When
        $this->dispatch(new IssueApiKeyCredential($id, $identity->id->toString(), 'CI pipeline', $keyId, 'plain-secret'));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertSame($id, $result->id);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('CI pipeline', $result->label);
        self::assertFalse($result->revoked);
        self::assertTrue($result->identityAuthenticatable);
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
        $this->dispatch(new IssueApiKeyCredential(Uuid::uuid7()->toString(), $identity->id->toString(), 'CI pipeline', KeyId::PREFIX.'0123456789abcdef', 'plain-secret'));
    }
}
