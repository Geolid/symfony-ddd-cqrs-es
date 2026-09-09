<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Finder;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Payment;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<PaymentResult>
 */
final class DbalPaymentFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $orderPayment = PaymentBuilder::new()->authorized()->create();
        $this->store($other, $orderPayment);

        // When
        $result = $this->finder()->ofId($orderPayment->id->toString());

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
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $requestedAt = Clock::get()->now()->modify('-4 days');
        $authorizedAt = $requestedAt->modify('+1 hour');
        $capturedAt = $requestedAt->modify('+1 day 2 hours');
        $paymentFactory = PaymentBuilder::new()
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($order->id->toString(), $capturedAt);
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $result = $this->finder()->ofReference($paymentFactory['reference']->value);

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($paymentFactory['cartId'], $result->cartId);
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($paymentFactory['amount']->cents, $result->amountInCents);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
        self::assertSame($paymentFactory['checkoutUrl'], $result->checkoutUrl);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
        self::assertSame($requestedAt->format('Y-m-d H:i:s'), $result->requestedAt->format('Y-m-d H:i:s'));
        self::assertSame($authorizedAt->format('Y-m-d H:i:s'), $result->authorizedAt?->format('Y-m-d H:i:s'));
        self::assertSame($capturedAt->format('Y-m-d H:i:s'), $result->capturedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->failedAt);
        self::assertNull($result->abandonedAt);
        self::assertNull($result->voidedAt);
    }

    #[Test]
    public function itThrowsOnUnknownReference(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofReference(PaymentBuilder::sample('reference')->value);
    }

    #[Test]
    public function itGetsByCartId(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($other, $orderPayment);

        // When
        $result = $this->finder()->ofCartId($paymentBuilder['cartId']);

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
    }

    #[Test]
    public function itThrowsWhenCartIdNotFound(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofCartId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itGetsByOrderId(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $order = OrderBuilder::new()->create();
        $orderPayment = PaymentBuilder::new()->authorized()->captured($order->id->toString())->create();
        $this->store($other, $orderPayment);

        // When
        $result = $this->finder()->ofOrderId($order->id->toString());

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
    }

    #[Test]
    public function itThrowsWhenOrderIdNotFound(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofOrderId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $authorized = PaymentBuilder::new()->authorized()->create();
        $requested = PaymentBuilder::new()->create();
        $this->store($authorized, $requested);

        // When
        $results = iterator_to_array($this->finder()->byStatus(PaymentStatus::REQUESTED));

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
        $this->store($freshRequested, $staleRequested);

        // When
        $results = iterator_to_array($this->finder()->stalledBefore($now));

        // Then
        self::assertCount(1, $results);
        self::assertSame($staleRequested->id->toString(), $results[0]->id);
    }

    protected function finder(): PaymentFinderInterface
    {
        return $this->service(PaymentFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $payments = PaymentBuilder::new()->many($count)->create();
        $this->store(...$payments);

        return array_map(static fn (Payment $payment): string => $payment->id->toString(), $payments);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
