<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\TrustDevice;

use Iam\Authentication\Application\Command\TrustDevice\TrustDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceTrusted;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class TrustDeviceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itTrusts(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identityId = Uuid::uuid7()->toString();
        $userAgent = faker()->userAgent();
        $ip = faker()->ipv4();
        $now = Clock::get()->now();
        $expirySeconds = self::getContainer()->getParameter('iam.authentication.trusted_device_expiry_seconds');
        self::assertIsInt($expirySeconds);
        $expiresAt = $now->modify(\sprintf('+%d seconds', $expirySeconds));

        // When
        $this->dispatch(new TrustDevice($id, $identityId, $userAgent, $ip));

        // Then
        $results = iterator_to_array($this->service(TrustedDeviceFinderInterface::class)->activeByIdentity($identityId, $now), false);
        self::assertCount(1, $results);

        $result = $results[0];
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($userAgent, $result->userAgent);
        self::assertSame($ip, $result->ip);
        $event = $this->publishedEventOf(TrustedDeviceTrusted::class);
        self::assertSameDateTime($expiresAt, $event->expiresAt);
    }
}
