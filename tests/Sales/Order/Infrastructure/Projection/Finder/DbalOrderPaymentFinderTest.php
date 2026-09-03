<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
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
    public function itGetsById(): void
    {
        // Given
        $other = OrderPaymentBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->authorized()->create();
        $this->store($other, $orderPayment);

        // When
        $result = $this->finder->ofId($orderPayment->id->toString());

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->finder->ofId(OrderPaymentBuilder::new()->create()->id->toString());
    }

    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $requestedAt = Clock::get()->now()->modify('-4 days');
        $authorizedAt = $requestedAt->modify('+1 hour');
        $capturedAt = $requestedAt->modify('+1 day 2 hours');
        $refundInitiatedAt = $requestedAt->modify('+2 days 3 hours');
        $refundedAt = $requestedAt->modify('+3 days 4 hours');
        $paymentFactory = OrderPaymentBuilder::new()
            ->withOrderId($order->id->toString())
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($capturedAt)
            ->refundInitiated($refundInitiatedAt)
            ->refundConfirmed($refundedAt);
        $orderPayment = $paymentFactory->create();
        $reference = $paymentFactory->attribute('reference')->value;
        $checkoutUrl = $paymentFactory->attribute('checkoutUrl');
        $amountInCents = $paymentFactory->attribute('amount')->cents;
        $this->store($order, $orderPayment);

        // When
        $result = $this->finder->ofReference($reference);

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($amountInCents, $result->amountInCents);
        self::assertSame($reference, $result->reference);
        self::assertSame($checkoutUrl, $result->checkoutUrl);
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
        $authorized = OrderPaymentBuilder::new()->authorized()->create();
        $requested = OrderPaymentBuilder::new()->create();
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
        $freshRequested = OrderPaymentBuilder::new()->withRequestedAt($now->modify('+1 day'))->create();
        $staleRequested = OrderPaymentBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $staleOrder = OrderBuilder::new()->create();
        $staleRefundInitiated = OrderPaymentBuilder::new()
            ->withOrderId($staleOrder->id->toString())
            ->withRequestedAt($now->modify('-4 days'))
            ->authorized($now->modify('-4 days')->modify('+10 minutes'))
            ->captured($now->modify('-3 days'))
            ->refundInitiated($now->modify('-1 day'))
            ->create();
        $freshOrder = OrderBuilder::new()->create();
        $freshRefundInitiated = OrderPaymentBuilder::new()
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
