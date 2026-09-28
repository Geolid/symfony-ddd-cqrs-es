<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustFinderInterface;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalDeviceTrustFinderTest extends AbstractIntegrationTestCase
{
    private DeviceTrustFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(DeviceTrustFinderInterface::class);
    }

    #[Test]
    public function itFindsByIdentity(): void
    {
        // Given
        $other = DeviceTrustBuilder::new()->create();

        $builder = DeviceTrustBuilder::new();
        $deviceTrust = $builder->create();
        $this->store($other, $deviceTrust);

        // When
        $result = $this->finder->ofIdentityOrNull($builder['identityId']);
        $nothing = $this->finder->ofIdentityOrNull(DeviceTrustBuilder::sample('identityId'));

        // Then
        self::assertNotNull($result);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame(
            $builder['revokedAt']->format(\DateTimeInterface::ATOM),
            $result->revokedAt->format(\DateTimeInterface::ATOM),
        );

        self::assertNull($nothing);
    }
}
