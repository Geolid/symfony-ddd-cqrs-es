<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Processor;

use Iam\Authentication\Application\Processor\ReleaseApiKeyLabelsOnIdentityErased;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class ReleaseApiKeyLabelsOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private ReleaseApiKeyLabelsOnIdentityErased $processor;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReleaseApiKeyLabelsOnIdentityErased::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleasesEveryLabelOnIdentityErased(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $otherIdentityId = Uuid::uuid7()->toString();
        $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), 'CI pipeline', Uuid::uuid7()->toString());
        $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), 'CD deploy', Uuid::uuid7()->toString());
        $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $otherIdentityId), 'CI pipeline', Uuid::uuid7()->toString());

        // When
        ($this->processor)(new IdentityErasedIntegrationEvent($identityId, '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), 'CI pipeline'));
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), 'CD deploy'));
        self::assertTrue($this->uniqueValues->exists(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $otherIdentityId), 'CI pipeline'));
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        ($this->processor)(new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::expectNotToPerformAssertions();
    }
}
