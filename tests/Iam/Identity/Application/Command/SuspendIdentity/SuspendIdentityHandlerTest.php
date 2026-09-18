<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
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
        $reason = IdentityBuilder::sample('reason')->value;
        $now = Clock::get()->now();

        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityModerationStatus::SUSPENDED, $result->moderationStatus);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
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
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), IdentityBuilder::sample('reason')->value));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame(IdentityModerationStatus::SUSPENDED, $result->moderationStatus);
        self::assertSame(IdentityVerificationStatus::PENDING, $result->verificationStatus);
    }

    #[Test]
    public function itIgnoresWhenAlreadySuspended(): void
    {
        // Given
        $builder = IdentityBuilder::new()->confirmed()->suspended();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), $builder['reason']->value));

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
            IdentityBuilder::sample('reason')->value,
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new SuspendIdentity(
            $identity->id->toString(),
            IdentityBuilder::sample('reason')->value,
        ));
    }
}
