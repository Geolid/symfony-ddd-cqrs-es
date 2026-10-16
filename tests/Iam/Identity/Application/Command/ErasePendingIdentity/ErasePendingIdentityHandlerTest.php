<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ErasePendingIdentity;

use Iam\Identity\Application\Command\ErasePendingIdentity\ErasePendingIdentity;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ErasePendingIdentityHandlerTest extends AbstractIntegrationTestCase
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
        $identity = IdentityFactory::new()
            ->withExpiry('24 hours')
            ->withRegisteredAt(Clock::get()->now()->modify('-24 hours -1 second'))
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($identity->id->toString());
    }

    #[Test]
    public function itIgnoresWhenConfirmed(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '24 hours';
        $identity = IdentityFactory::new()
            ->withExpiry($expiry)
            ->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))
            ->confirmed()
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
    }

    #[Test]
    public function itIgnoresWhenNotExpired(): void
    {
        // Given
        $identity = IdentityFactory::new()
            ->withExpiry('24 hours')
            ->withRegisteredAt(Clock::get()->now()->modify('-24 hours +1 second'))
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '24 hours';
        $identity = IdentityFactory::new()
            ->withExpiry($expiry)
            ->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))
            ->erasureRequested()
            ->erased()
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = IdentityIdFactory::new()->create()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ErasePendingIdentity($unknownId));
    }
}
