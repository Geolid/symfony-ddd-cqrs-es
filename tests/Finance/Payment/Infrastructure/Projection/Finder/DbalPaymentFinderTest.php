<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Finder;

use Finance\Payment\Application\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DbalPaymentFinderTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $orderPayment = PaymentBuilder::new()->authorized()->create();
        $this->store($other, $orderPayment);

        // When
        $result = $this->finder->ofId($orderPayment->id->toString());

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder->ofId(PaymentId::forOrder(Uuid::uuid7()->toString())->toString());
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
        $paymentFactory = PaymentBuilder::new()
            ->withOrderId($order->id->toString())
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($capturedAt)
            ->refundInitiated($refundInitiatedAt)
            ->refundConfirmed($refundedAt);
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $result = $this->finder->ofReference($paymentFactory['reference']->value);

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($paymentFactory['amount']->cents, $result->amountInCents);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
        self::assertSame($paymentFactory['checkoutUrl'], $result->checkoutUrl);
        self::assertSame(PaymentStatus::REFUNDED, $result->status);
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
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder->ofReference(PaymentBuilder::sample('reference')->value);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $authorized = PaymentBuilder::new()->authorized()->create();
        $requested = PaymentBuilder::new()->create();
        $this->store($authorized, $requested);

        // When
        $results = iterator_to_array($this->finder->byStatus(PaymentStatus::REQUESTED));

        // Then
        self::assertCount(1, $results);
        self::assertSame($requested->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshRequested = PaymentBuilder::new()->withRequestedAt($now->modify('+1 day'))->create();
        $staleRequested = PaymentBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $staleOrder = OrderBuilder::new()->create();
        $staleRefundInitiated = PaymentBuilder::new()
            ->withOrderId($staleOrder->id->toString())
            ->withRequestedAt($now->modify('-4 days'))
            ->authorized($now->modify('-4 days')->modify('+10 minutes'))
            ->captured($now->modify('-3 days'))
            ->refundInitiated($now->modify('-1 day'))
            ->create();
        $freshOrder = OrderBuilder::new()->create();
        $freshRefundInitiated = PaymentBuilder::new()
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
            array_map(static fn (PaymentResult $result): string => $result->id, $results),
        );
    }
}
