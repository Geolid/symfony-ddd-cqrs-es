<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Application\Command\ReactivateIdentity\ReactivateIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReactivates(): void
    {
        // Given
        $reason = IdentityBuilder::sample('reason')->value;
        $now = Clock::get()->now();

        $builder = IdentityBuilder::new()->suspended();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
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
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityBuilder::sample('reason')->value,
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
            IdentityId::generate()->toString(),
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
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityBuilder::sample('reason')->value,
        ));
    }
}
