<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Finder;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<PaymentResult>
 */
final class DbalPaymentFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = PaymentFactory::new()->create();
        $payment = PaymentFactory::new()->authorized()->create();
        $this->store($other, $payment);

        // When
        $result = $this->finder()->ofId($payment->id->toString());

        // Then
        self::assertSame($payment->id->toString(), $result->id);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Given
        $unknownId = PaymentIdFactory::new()->create()->toString();

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofId($unknownId);
    }

    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $requestedAt = Clock::get()->now()->modify('-4 days');
        $authorizedAt = $requestedAt->modify('+1 hour');
        $capturedAt = $requestedAt->modify('+1 day 2 hours');
        $payment = PaymentFactory::new()
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($order->id->toString(), $capturedAt)
            ->create();
        $this->store($order, $payment);

        // When
        $result = $this->finder()->ofReference($payment->reference->value);

        // Then
        self::assertSame($payment->id->toString(), $result->id);
        self::assertSame($payment->checkoutSessionId, $result->checkoutSessionId);
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($payment->amount->cents, $result->amountInCents);
        self::assertSame($payment->reference->value, $result->reference);
        self::assertSame($payment->hostedPageUrl, $result->hostedPageUrl);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
        self::assertSameDateTime($requestedAt, $result->requestedAt);
        self::assertSameDateTime($authorizedAt, $result->authorizedAt);
        self::assertSameDateTime($capturedAt, $result->capturedAt);
        self::assertNull($result->failedAt);
        self::assertNull($result->abandonedAt);
        self::assertNull($result->voidedAt);
    }

    #[Test]
    public function itThrowsOnUnknownReference(): void
    {
        // Given
        $unknownReference = PaymentReferenceFactory::new()->create()->value;

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofReference($unknownReference);
    }

    #[Test]
    public function itGetsByCheckoutSession(): void
    {
        // Given
        $other = PaymentFactory::new()->create();
        $payment = PaymentFactory::new()->create();
        $this->store($other, $payment);

        // When
        $result = $this->finder()->ofCheckoutSession($payment->checkoutSessionId);

        // Then
        self::assertSame($payment->id->toString(), $result->id);
    }

    #[Test]
    public function itThrowsWhenCheckoutSessionNotFound(): void
    {
        // Given
        $unknownCheckoutSessionId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofCheckoutSession($unknownCheckoutSessionId);
    }

    #[Test]
    public function itGetsByOrderId(): void
    {
        // Given
        $other = PaymentFactory::new()->create();
        $order = OrderFactory::new()->create();
        $payment = PaymentFactory::new()->authorized()->captured($order->id->toString())->create();
        $this->store($other, $payment);

        // When
        $result = $this->finder()->ofOrderId($order->id->toString());

        // Then
        self::assertSame($payment->id->toString(), $result->id);
    }

    #[Test]
    public function itThrowsWhenOrderIdNotFound(): void
    {
        // Given
        $unknownOrderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->finder()->ofOrderId($unknownOrderId);
    }

    #[Test]
    public function itFiltersUnreconciledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshRequested = PaymentFactory::new()->withRequestedAt($now->modify('+1 day'))->create();
        $requestedAtCutoff = PaymentFactory::new()->withRequestedAt($now)->create();
        $staleRequested = PaymentFactory::new()->withRequestedAt($now->modify('-1 day'))->create();
        $this->store($freshRequested, $requestedAtCutoff, $staleRequested);

        // When
        $results = iterator_to_array($this->finder()->unreconciledBefore($now));

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
        $payments = PaymentFactory::new()->many($count)->create();
        $this->store(...$payments);

        return array_map(static fn (Payment $payment): string => $payment->id->toString(), $payments);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $checkoutSessionIdByPaymentId = [];
        foreach ([Uuid::uuid7()->toString(), Uuid::uuid7()->toString()] as $checkoutSessionId) {
            $checkoutSessionIdByPaymentId[PaymentId::forCheckoutSession($checkoutSessionId)->toString()] = $checkoutSessionId;
        }
        ksort($checkoutSessionIdByPaymentId);
        [$smallerId, $largerId] = array_keys($checkoutSessionIdByPaymentId);

        $now = Clock::get()->now();
        $first = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionIdByPaymentId[$largerId])->withRequestedAt($now)->create();
        $second = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionIdByPaymentId[$smallerId])->withRequestedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
