<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\ReleaseApiKeyLabelsOnIdentityErased;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseApiKeyLabelsOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private ReleaseApiKeyLabelsOnIdentityErased $policy;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(ReleaseApiKeyLabelsOnIdentityErased::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = ApiKeyCredentialTestFactory::sample('identityId');
        $otherIdentityId = ApiKeyCredentialTestFactory::sample('identityId');

        $label = ApiKeyCredentialTestFactory::sample('label')->value;
        $otherLabel = ApiKeyCredentialTestFactory::sample('label')->value;

        $this->reserveLabel($identityId, $label);
        $this->reserveLabel($identityId, $otherLabel);
        $this->reserveLabel($otherIdentityId, $label);

        // When
        ($this->policy)(new IdentityErasedIntegrationEvent($identityId, Clock::get()->now()));

        // Then
        $key = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId);
        $otherKey = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $otherIdentityId);

        self::assertFalse($this->uniqueValues->exists($key, $label));
        self::assertFalse($this->uniqueValues->exists($key, $otherLabel));
        self::assertTrue($this->uniqueValues->exists($otherKey, $label));
    }

    private function reserveLabel(string $identityId, string $label): void
    {
        $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identityId), $label, ApiKeyCredentialTestFactory::sample('id')->toString());
    }
}
