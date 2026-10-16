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
use Iam\Tests\Identity\Support\Factory\ReasonFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itReactivates(): void
    {
        // Given
        $reason = ReasonFactory::new()->create()->value;
        $now = Clock::get()->now();

        $factory = IdentityFactory::new()->confirmed()->suspended();
        $identity = $factory->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
        self::assertSame($reason, $result->reason);
        self::assertSameDateTime($identity->registeredAt, $result->registeredAt);
        self::assertSameDateTime($now, $result->reactivatedAt);
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
            ReasonFactory::new()->create()->value,
        ));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
        self::assertNull($result->reactivatedAt);
        self::assertNull($result->reason);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = IdentityIdFactory::new()->create()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ReactivateIdentity(
            $unknownId,
            ReasonFactory::new()->create()->value,
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
            ReasonFactory::new()->create()->value,
        ));
    }
}
