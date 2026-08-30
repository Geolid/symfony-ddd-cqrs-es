<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DbalOrderPaymentFinderTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $requestedAt = Clock::get()->now()->modify('-4 days');
        $authorizedAt = $requestedAt->modify('+1 hour');
        $capturedAt = $requestedAt->modify('+1 day 2 hours');
        $refundInitiatedAt = $requestedAt->modify('+2 days 3 hours');
        $refundedAt = $requestedAt->modify('+3 days 4 hours');
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://checkout.globex.test/pay/GLBX-9F3K2M1P')
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($capturedAt)
            ->refundInitiated($refundInitiatedAt)
            ->refundConfirmed($refundedAt)
            ->create();
        $this->store($order, $orderPayment);

        // When
        $result = $this->finder->ofReference('GLBX-9F3K2M1P');

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame('https://checkout.globex.test/pay/GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REFUNDED, $result->status);
        self::assertSame($requestedAt->format('Y-m-d H:i:s'), $result->requestedAt->format('Y-m-d H:i:s'));
        self::assertSame($authorizedAt->format('Y-m-d H:i:s'), $result->authorizedAt?->format('Y-m-d H:i:s'));
        self::assertSame($capturedAt->format('Y-m-d H:i:s'), $result->capturedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->failedAt);
        self::assertNull($result->cancelledAt);
        self::assertSame($refundInitiatedAt->format('Y-m-d H:i:s'), $result->refundInitiatedAt?->format('Y-m-d H:i:s'));
        self::assertSame($refundedAt->format('Y-m-d H:i:s'), $result->refundedAt?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsOnUnknownReference(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->finder->ofReference('GLBX-NEVER-ISSUED');
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $authorized = OrderPaymentTestFactory::new()->authorized()->create();
        $requested = OrderPaymentTestFactory::new()->create();
        $this->store($authorized, $requested);

        // When
        $results = iterator_to_array($this->finder->byStatus(OrderPaymentStatus::REQUESTED));

        // Then
        self::assertCount(1, $results);
        self::assertSame($requested->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshRequested = OrderPaymentTestFactory::new()->withRequestedAt($now->modify('+1 day'))->create();
        $staleRequested = OrderPaymentTestFactory::new()->withRequestedAt($now->modify('-1 day'))->create();
        $staleOrder = OrderTestFactory::new()->create();
        $staleRefundInitiated = OrderPaymentTestFactory::new()
            ->withOrderId($staleOrder->id->toString())
            ->withRequestedAt($now->modify('-4 days'))
            ->authorized($now->modify('-4 days')->modify('+10 minutes'))
            ->captured($now->modify('-3 days'))
            ->refundInitiated($now->modify('-1 day'))
            ->create();
        $freshOrder = OrderTestFactory::new()->create();
        $freshRefundInitiated = OrderPaymentTestFactory::new()
            ->withOrderId($freshOrder->id->toString())
            ->withRequestedAt($now->modify('-4 days'))
            ->authorized($now->modify('-4 days')->modify('+10 minutes'))
            ->captured($now->modify('-3 days'))
            ->refundInitiated($now->modify('+1 day'))
            ->create();
        $this->store($staleOrder, $freshOrder, $freshRequested, $staleRequested, $staleRefundInitiated, $freshRefundInitiated);

        // When
        $results = iterator_to_array($this->finder->stalledBefore($now));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$staleRequested->id->toString(), $staleRefundInitiated->id->toString()],
            array_map(static fn (OrderPaymentResult $result): string => $result->id, $results),
        );
    }
}
