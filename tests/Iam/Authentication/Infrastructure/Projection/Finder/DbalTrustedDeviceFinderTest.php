<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use PHPUnit\Framework\Attributes\Test;
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
        $builder = TrustedDeviceBuilder::new();
        $trustedDevice = $builder->create();

        $other = TrustedDeviceBuilder::new()->create();
        $revoked = TrustedDeviceBuilder::new()->withIdentityId($builder['identityId'])->revoked()->create();

        $this->store($other, $revoked, $trustedDevice);

        // When
        $results = iterator_to_array($this->finder()->activeByIdentity($builder['identityId']));

        // Then
        self::assertCount(1, $results);
        self::assertSame($trustedDevice->id->toString(), $results[0]->id);
        self::assertSame($builder['identityId'], $results[0]->identityId);
        self::assertSame($builder['userAgent'], $results[0]->userAgent);
        self::assertSame($builder['ip'], $results[0]->ip);
    }

    #[Test]
    public function itExcludesExpiredFromActiveByIdentity(): void
    {
        // Given
        $lifetime = self::getContainer()->getParameter('iam.authentication.trusted_device_lifetime');
        self::assertIsInt($lifetime);

        $now = Clock::get()->now();
        $identityId = TrustedDeviceBuilder::sample('identityId');

        $expired = TrustedDeviceBuilder::new()
            ->withIdentityId($identityId)
            ->withTrustedAt($now->modify(\sprintf('-%d seconds', $lifetime + 1)))
            ->create();

        $stillActive = TrustedDeviceBuilder::new()
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
        $now = Clock::get()->now();

        $trustedDevices = [];
        for ($i = 0; $i < $count; ++$i) {
            $trustedDevices[] = TrustedDeviceBuilder::new()
                ->withTrustedAt($now->modify(\sprintf('+%d minutes', $i)))
                ->create();
        }

        $this->store(...$trustedDevices);

        return array_reverse(array_map(
            static fn (object $trustedDevice): string => $trustedDevice->id->toString(),
            $trustedDevices,
        ));
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }
}
