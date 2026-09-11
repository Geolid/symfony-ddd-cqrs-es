<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\StaleCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class StaleCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itStales(): void
    {
        // Given
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new StaleCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofCartOrNull($checkoutSessionBuilder['cartId']);
        self::assertNotNull($result);
        self::assertSame(CheckoutSessionStatus::STALE, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyStaled(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->staled()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new StaleCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new StaleCheckoutSession(Uuid::uuid7()->toString()));
    }
}
