<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeTrustedDevice;

use Iam\Authentication\Application\Command\RevokeTrustedDevice\RevokeTrustedDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RevokeTrustedDeviceHandlerTest extends AbstractIntegrationTestCase
{
    private TrustedDeviceFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TrustedDeviceFinderInterface::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        // Given
        $builder = TrustedDeviceBuilder::new();
        $trustedDevice = $builder->create();
        $this->store($trustedDevice);

        // When
        $this->dispatch(new RevokeTrustedDevice($trustedDevice->id->toString(), $builder['identityId']));

        // Then
        self::assertCount(0, $this->finder->activeByIdentity($builder['identityId']));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $builder = TrustedDeviceBuilder::new()->revoked();
        $trustedDevice = $builder->create();
        $this->store($trustedDevice);

        // When
        $this->dispatch(new RevokeTrustedDevice($trustedDevice->id->toString(), $builder['identityId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TrustedDeviceNotFoundException::class);

        // When
        $this->dispatch(new RevokeTrustedDevice(
            Uuid::uuid7()->toString(),
            TrustedDeviceBuilder::sample('identityId'),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $trustedDevice = TrustedDeviceBuilder::new()->create();
        $this->store($trustedDevice);

        // Then
        $this->expectException(TrustedDeviceOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeTrustedDevice(
            $trustedDevice->id->toString(),
            TrustedDeviceBuilder::sample('identityId'),
        ));
    }
}
