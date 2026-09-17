<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseUnconfirmedIdentity;

use Iam\Identity\Application\Command\EraseUnconfirmedIdentity\EraseUnconfirmedIdentity;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseUnconfirmedIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-25 hours'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseUnconfirmedIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($identity->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-25 hours'))->activated()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseUnconfirmedIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
    }

    #[Test]
    public function itIgnoresWhenNotYetExpired(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseUnconfirmedIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()
            ->withRegisteredAt($now->modify('-25 hours'))
            ->erasureRequested()
            ->erased()
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseUnconfirmedIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new EraseUnconfirmedIdentity(Uuid::uuid7()->toString()));
    }
}
