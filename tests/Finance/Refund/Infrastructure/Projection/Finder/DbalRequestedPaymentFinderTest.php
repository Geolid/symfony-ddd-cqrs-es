<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Infrastructure\Projection\Finder;

use Finance\Refund\Application\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\RequestedPayment\RequestedPaymentFinderInterface;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalRequestedPaymentFinderTest extends AbstractIntegrationTestCase
{
    private RequestedPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(RequestedPaymentFinderInterface::class);
    }

    #[Test]
    public function itGetsByOrder(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $builder = PaymentBuilder::new();
        $payment = $builder->create();
        $this->store($other, $payment);

        // When
        $result = $this->finder->ofOrder($builder['orderId']);

        // Then
        self::assertSame($builder['orderId'], $result->orderId);
        self::assertSame($payment->id->toString(), $result->paymentId);
        self::assertSame($builder['amount']->cents, $result->amountInCents);
    }

    #[Test]
    public function itThrowsWhenOrderNotFound(): void
    {
        // Then
        $this->expectException(RequestedPaymentResultNotFoundException::class);

        // When
        $this->finder->ofOrder(Uuid::uuid7()->toString());
    }
}
