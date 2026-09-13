<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\ExpireCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\ExpireCheckoutSession\ExpireCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofCartOrNull($checkoutSessionBuilder['cartId']);
        self::assertNotNull($result);
        self::assertSame(CheckoutSessionStatus::EXPIRED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyExpired(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->expired()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new ExpireCheckoutSession(Uuid::uuid7()->toString()));
    }
}
