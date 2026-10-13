<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Application\Command\ReactivateIdentity\ReactivateIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReactivates(): void
    {
        // Given
        $reason = IdentityFactory::sample('reason')->value;
        $now = Clock::get()->now();

        $factory = IdentityFactory::new()->confirmed()->suspended();
        $identity = $factory->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $identity->registeredAt->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->reactivatedAt?->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->suspendedAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyActive(): void
    {
        // Given
        $identity = IdentityFactory::new()->confirmed()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityFactory::sample('reason')->value,
        ));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ReactivateIdentity(
            IdentityIdFactory::new()->create()->toString(),
            IdentityFactory::sample('reason')->value,
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityFactory::sample('reason')->value,
        ));
    }
}
