<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RegisterIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = IdentityBuilder::sample('id')->toString();
        $now = Clock::get()->now();

        // When
        $this->dispatch(new RegisterIdentity($id));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertNull($result->reason);
        self::assertSame(
            $now->format(\DateTimeImmutable::ATOM),
            $result->registeredAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertNull($result->suspendedAt);
        self::assertNull($result->reactivatedAt);
    }
}
