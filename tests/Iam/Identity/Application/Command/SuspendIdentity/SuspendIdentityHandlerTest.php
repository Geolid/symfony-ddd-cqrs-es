<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class SuspendIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itSuspends(): void
    {
        // Given
        $reason = IdentityBuilder::sample('reason')->value;
        $now = Clock::get()->now();

        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityStatus::SUSPENDED, $result->status);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeImmutable::ATOM),
            $result->registeredAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertSame(
            $now->format(\DateTimeImmutable::ATOM),
            $result->suspendedAt?->format(\DateTimeImmutable::ATOM),
        );
        self::assertNull($result->reactivatedAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadySuspended(): void
    {
        // Given
        $builder = IdentityBuilder::new()->suspended();
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
            IdentityBuilder::sample('id')->toString(),
            IdentityBuilder::sample('reason')->value,
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erased()->create();
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
