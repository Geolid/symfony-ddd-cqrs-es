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
        $expiry = '30 minutes';
        $checkoutSession = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt(Clock::get()->now()->modify(\sprintf('-%s -1 second', $expiry)))
            ->create();
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
    public function itIgnoresWhenNotExpired(): void
    {
        // Given
        $expiry = '30 minutes';
        $checkoutSession = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt(Clock::get()->now()->modify(\sprintf('-%s +1 second', $expiry)))
            ->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofId($checkoutSession->id->toString());
        self::assertSame(CheckoutSessionStatus::OPEN, $result->status);
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
