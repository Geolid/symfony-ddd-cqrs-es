<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Processor;

use Iam\Identity\Application\Processor\ReleaseLabelOnApiTokenCredentialRevoked;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueValue;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ReleaseLabelOnApiTokenCredentialRevokedTest extends AbstractIntegrationTestCase
{
    private ReleaseLabelOnApiTokenCredentialRevoked $processor;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReleaseLabelOnApiTokenCredentialRevoked::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleasesTheLabelOnApiTokenCredentialRevoked(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()->withLabel('CI pipeline')->withHasher(new DummySecretHasher())->create();
        $this->store($credential);
        $fingerprint = $credential->label()->fingerprintFor($credential->identityId()->toString());
        $this->uniqueValues->reserve(ApiTokenCredentialUniqueValue::LABEL, $fingerprint);

        // When
        ($this->processor)(new ApiTokenCredentialRevoked($credential->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(ApiTokenCredentialUniqueValue::LABEL, $fingerprint));
    }
}
