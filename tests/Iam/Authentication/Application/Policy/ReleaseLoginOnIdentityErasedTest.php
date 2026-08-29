<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\ReleaseLoginOnIdentityErased;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ReleaseLoginOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private ReleaseLoginOnIdentityErased $policy;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(ReleaseLoginOnIdentityErased::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $ownerId = PasswordCredentialId::forIdentity($identityId)->toString();
        $this->uniqueValues->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace', $ownerId);

        // When
        ($this->policy)(new IdentityErasedIntegrationEvent($identityId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace'));
    }
}
