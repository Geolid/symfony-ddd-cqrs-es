<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Processor;

use Iam\Identity\Application\Processor\ReleaseLabelOnApiTokenCredentialRevoked;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueKey;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
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
        $credential = ApiTokenCredentialTestFactory::new()->withLabel('CI pipeline')->withHasher(new FakeSecretHasher())->store();
        $key = UniqueKey::for(ApiTokenCredentialUniqueKey::LABEL, $credential->identityId->toString());
        $this->uniqueValues->reserve($key, $credential->label->value, $credential->id->toString());

        // When
        ($this->processor)(new ApiTokenCredentialRevoked($credential->id->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists($key, $credential->label->value));
    }
}
