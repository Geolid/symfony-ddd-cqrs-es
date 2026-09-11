<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\ConsumeCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\ConsumeCheckoutSession\ConsumeCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConsumeCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itConsumes(): void
    {
        // Given
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ConsumeCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofCartOrNull($checkoutSessionBuilder['cartId']);
        self::assertNotNull($result);
        self::assertSame(CheckoutSessionStatus::CONSUMED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConsumed(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->consumed()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ConsumeCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new ConsumeCheckoutSession(Uuid::uuid7()->toString()));
    }
}
