<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Uniqueness;

use Iam\Authentication\Application\Uniqueness\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Infrastructure\Uniqueness\ReleaseApiKeyLabelsOnIdentityErased;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseApiKeyLabelsOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $otherIdentityId = ApiKeyCredentialBuilder::sample('identityId');

        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $otherLabel = ApiKeyCredentialBuilder::sample('label')->value;

        $this->claimLabel($identityId, $label);
        $this->claimLabel($identityId, $otherLabel);
        $this->claimLabel($otherIdentityId, $label);

        // When
        $this->trigger(ReleaseApiKeyLabelsOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, Clock::get()->now()));

        // Then
        $key = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId);
        $otherKey = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $otherIdentityId);

        self::assertFalse($this->uniqueValues->isClaimed($key, $label));
        self::assertFalse($this->uniqueValues->isClaimed($key, $otherLabel));
        self::assertTrue($this->uniqueValues->isClaimed($otherKey, $label));
    }

    private function claimLabel(string $identityId, string $label): void
    {
        $this->uniqueValues->claim(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), $label, Uuid::uuid7()->toString());
    }
}
