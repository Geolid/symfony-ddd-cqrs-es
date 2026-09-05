<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Infrastructure\Projection\Projector\DbalPaymentProjector;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, amount_in_cents: int|string, reference: string, checkout_url: string, status: string, authorized_at: ?string, captured_at: ?string, failed_at: ?string, cancelled_at: ?string}
 */
final class DbalPaymentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnPaymentRequested(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame($paymentBuilder['orderId'], $row['order_id']);
        self::assertSame($paymentBuilder['amount']->cents, (int) $row['amount_in_cents']);
        self::assertSame($paymentBuilder['reference']->value, $row['reference']);
        self::assertSame($paymentBuilder['checkoutUrl'], $row['checkout_url']);
        self::assertSame(PaymentStatus::REQUESTED->value, $row['status']);
        self::assertNull($row['authorized_at']);
        self::assertNull($row['captured_at']);
        self::assertNull($row['failed_at']);
        self::assertNull($row['cancelled_at']);
    }

    #[Test]
    public function itProjectsOnPaymentAuthorized(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $this->store($other);
        $orderPayment = PaymentBuilder::new()->authorized()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(PaymentStatus::AUTHORIZED->value, $row['status']);
        self::assertNotNull($row['authorized_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(PaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnPaymentCaptured(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $other = PaymentBuilder::new()->create();
        $this->store($order, $other);
        $orderPayment = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(PaymentStatus::CAPTURED->value, $row['status']);
        self::assertNotNull($row['captured_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(PaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnPaymentFailed(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $this->store($other);
        $orderPayment = PaymentBuilder::new()->failed()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(PaymentStatus::FAILED->value, $row['status']);
        self::assertNotNull($row['failed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(PaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnPaymentCancelled(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $this->store($other);
        $orderPayment = PaymentBuilder::new()->cancelled()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(PaymentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(PaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnPaymentVoided(): void
    {
        // Given
        $other = PaymentBuilder::new()->authorized()->create();
        $this->store($other);
        $orderPayment = PaymentBuilder::new()->authorized()->cancelled()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(PaymentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(PaymentStatus::AUTHORIZED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT order_id, amount_in_cents, reference, checkout_url, status, authorized_at, captured_at, failed_at, cancelled_at FROM %s WHERE id = :id',
                DbalPaymentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
