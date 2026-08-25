<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Processor;

use Iam\Authentication\Application\Processor\ReleaseLoginOnIdentityErased;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
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
    public function itReleasesTheLoginOnIdentityErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('ada.lovelace')
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->store();
        $this->uniqueValues->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace', $credential->id->toString());

        // When
        ($this->processor)(new IdentityErasedIntegrationEvent($identity->id->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace'));
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
