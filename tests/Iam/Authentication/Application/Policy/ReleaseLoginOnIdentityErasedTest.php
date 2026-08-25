<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\ReleaseLoginOnIdentityErased;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class ReleaseLoginOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private ReleaseLoginOnIdentityErased $processor;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReleaseLoginOnIdentityErased::class);
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
        ($this->processor)(new IdentityErasedIntegrationEvent($identityId, '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace'));
    }
}
