<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class SuspendIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itSuspends(): void
    {
        // Given
        $reason = IdentityFactory::sample('reason')->value;
        $now = Clock::get()->now();

        $factory = IdentityFactory::new()->confirmed();
        $identity = $factory->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityModerationStatus::SUSPENDED, $result->moderationStatus);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $identity->registeredAt->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->suspendedAt?->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->reactivatedAt);
    }

    #[Test]
    public function itSuspendsWhenPending(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), IdentityFactory::sample('reason')->value));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame(IdentityModerationStatus::SUSPENDED, $result->moderationStatus);
        self::assertSame(IdentityVerificationStatus::PENDING, $result->verificationStatus);
    }

    #[Test]
    public function itIgnoresWhenAlreadySuspended(): void
    {
        // Given
        $factory = IdentityFactory::new()->confirmed()->suspended();
        $identity = $factory->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), IdentityFactory::inputs($identity)['reason']->value));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new SuspendIdentity(
            Uuid::uuid7()->toString(),
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
        $this->dispatch(new SuspendIdentity(
            $identity->id->toString(),
            IdentityFactory::sample('reason')->value,
        ));
    }
}
