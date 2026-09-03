<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\ReleaseApiKeyLabelsOnIdentityErased;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseApiKeyLabelsOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $otherIdentityId = ApiKeyCredentialBuilder::sample('identityId');

        $label = ApiKeyCredentialBuilder::sample('label')->value;
        $otherLabel = ApiKeyCredentialBuilder::sample('label')->value;

        $this->reserveLabel($identityId, $label);
        $this->reserveLabel($identityId, $otherLabel);
        $this->reserveLabel($otherIdentityId, $label);

        // When
        $this->trigger(ReleaseApiKeyLabelsOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, Clock::get()->now()));

        // Then
        $key = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId);
        $otherKey = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $otherIdentityId);

        self::assertFalse($this->uniqueValues->exists($key, $label));
        self::assertFalse($this->uniqueValues->exists($key, $otherLabel));
        self::assertTrue($this->uniqueValues->exists($otherKey, $label));
    }

    private function reserveLabel(string $identityId, string $label): void
    {
        $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), $label, ApiKeyCredentialId::generate()->toString());
    }
}
