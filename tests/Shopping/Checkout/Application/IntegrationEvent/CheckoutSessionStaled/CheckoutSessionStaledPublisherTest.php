<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\CheckoutSessionStaled;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionStaled\CheckoutSessionStaledIntegrationEvent;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionStaledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->staled()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $event = $this->publishedEventOf(CheckoutSessionStaledIntegrationEvent::class);
        self::assertSame($checkoutSession->id->toString(), $event->checkoutSessionId);
        self::assertSameDate($checkoutSession->staledAt, $event->staledAt);
    }
}
