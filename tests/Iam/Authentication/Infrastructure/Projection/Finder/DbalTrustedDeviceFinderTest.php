<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Tests\Authentication\Support\Factory\TrustedDeviceFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<TrustedDeviceResult>
 */
final class DbalTrustedDeviceFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersActiveByIdentity(): void
    {
        // Given
        $trustedDevice = TrustedDeviceFactory::new()->create();

        $other = TrustedDeviceFactory::new()->create();
        $revoked = TrustedDeviceFactory::new()->withIdentityId($trustedDevice->identityId)->revoked()->create();

        $this->store($other, $revoked, $trustedDevice);

        // When
        $results = iterator_to_array($this->finder()->activeByIdentity($trustedDevice->identityId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($trustedDevice->id->toString(), $results[0]->id);
        self::assertSame($trustedDevice->identityId, $results[0]->identityId);
        self::assertSame($trustedDevice->userAgent, $results[0]->userAgent);
        self::assertSame($trustedDevice->ip, $results[0]->ip);
    }

    #[Test]
    public function itExcludesExpiredFromActiveByIdentity(): void
    {
        // Given
        $lifetime = self::getContainer()->getParameter('iam.authentication.trusted_device_lifetime');
        self::assertIsInt($lifetime);

        $now = Clock::get()->now();
        $identityId = Uuid::uuid7()->toString();

        $expired = TrustedDeviceFactory::new()
            ->withIdentityId($identityId)
            ->withTrustedAt($now->modify(\sprintf('-%d seconds', $lifetime + 1)))
            ->create();

        $stillActive = TrustedDeviceFactory::new()
            ->withIdentityId($identityId)
            ->withTrustedAt($now->modify(\sprintf('-%d seconds', $lifetime)))
            ->create();

        $this->store($expired, $stillActive);

        // When
        $results = iterator_to_array($this->finder()->activeByIdentity($identityId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($stillActive->id->toString(), $results[0]->id);
    }

    protected function finder(): TrustedDeviceFinderInterface
    {
        return $this->service(TrustedDeviceFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $trustedDevices = TrustedDeviceFactory::new()->many($count)->create();
        $this->store(...$trustedDevices);

        return array_reverse(array_map(
            static fn (TrustedDevice $trustedDevice): string => $trustedDevice->id->toString(),
            $trustedDevices,
        ));
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }
}
