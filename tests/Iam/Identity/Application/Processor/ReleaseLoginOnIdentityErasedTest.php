<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Processor;

use Iam\Identity\Application\Processor\ReleaseLoginOnIdentityErased;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
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
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()->withIdentityId($identityId)->withLogin('operator')->withHasher(new DummySecretHasher())->store();
        $fingerprint = $credential->login()->fingerprint();
        $this->uniqueValues->reserve(PasswordCredentialUniqueValue::LOGIN, $fingerprint);

        // When
        ($this->processor)(new IdentityErased($identityId, '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(PasswordCredentialUniqueValue::LOGIN, $fingerprint));
    }

    #[Test]
    public function itDoesNothingWhenNoPasswordCredentialExistsForTheIdentity(): void
    {
        // When
        ($this->processor)(new IdentityErased(Uuid::uuid7()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::expectNotToPerformAssertions();
    }
}
