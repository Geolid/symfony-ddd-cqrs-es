<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\ExpireCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\ExpireCheckoutSession\ExpireCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class ExpireCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itExpires(): void
    {
        // Given
        $expirySeconds = faker()->numberBetween(60, 3600);
        $openedAt = Clock::get()->now()->modify(\sprintf('-%d seconds', $expirySeconds + 1));
        $checkoutSession = CheckoutSessionFactory::new()->withExpirySeconds($expirySeconds)->withOpenedAt($openedAt)->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofId($checkoutSession->id->toString());
        self::assertSame(CheckoutSessionStatus::EXPIRED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyExpired(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->expired()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenTTLNotElapsed(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = CheckoutSessionIdFactory::new()->create()->toString();

        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new ExpireCheckoutSession($unknownId));
    }
}
