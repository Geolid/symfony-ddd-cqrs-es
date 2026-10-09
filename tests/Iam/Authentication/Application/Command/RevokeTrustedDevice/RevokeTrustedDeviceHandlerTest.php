<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeTrustedDevice;

use Iam\Authentication\Application\Command\RevokeTrustedDevice\RevokeTrustedDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Tests\Authentication\Support\Factory\TrustedDeviceFactory;
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
        $trustedDevice = TrustedDeviceFactory::new()->create();
        $this->store($trustedDevice);

        // When
        $this->dispatch(new RevokeTrustedDevice($trustedDevice->id->toString(), $trustedDevice->identityId));

        // Then
        self::assertCount(0, $this->finder->activeByIdentity($trustedDevice->identityId));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $trustedDevice = TrustedDeviceFactory::new()->revoked()->create();
        $this->store($trustedDevice);

        // When
        $this->dispatch(new RevokeTrustedDevice($trustedDevice->id->toString(), $trustedDevice->identityId));

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
            Uuid::uuid7()->toString(),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $trustedDevice = TrustedDeviceFactory::new()->create();
        $this->store($trustedDevice);

        // Then
        $this->expectException(TrustedDeviceOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeTrustedDevice(
            $trustedDevice->id->toString(),
            Uuid::uuid7()->toString(),
        ));
    }
}
