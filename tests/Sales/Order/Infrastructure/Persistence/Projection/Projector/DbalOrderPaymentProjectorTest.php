<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, amount_in_cents: int|string, reference: string, checkout_url: string, status: string, authorized_at: ?string, captured_at: ?string, failed_at: ?string, cancelled_at: ?string, refund_initiated_at: ?string, refunded_at: ?string}
 */
final class DbalOrderPaymentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPaymentRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(4_200)
            ->withReference('GLBX-9F3K2M1P')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame($orderId, $row['order_id']);
        self::assertSame(4_200, (int) $row['amount_in_cents']);
        self::assertSame('GLBX-9F3K2M1P', $row['reference']);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $row['checkout_url']);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $row['status']);
        self::assertNull($row['authorized_at']);
        self::assertNull($row['captured_at']);
        self::assertNull($row['failed_at']);
        self::assertNull($row['cancelled_at']);
        self::assertNull($row['refund_initiated_at']);
        self::assertNull($row['refunded_at']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentAuthorized(): void
    {
        // Given
        $other = OrderPaymentTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->authorized()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::AUTHORIZED->value, $row['status']);
        self::assertNotNull($row['authorized_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentCaptured(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $other = OrderPaymentTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::CAPTURED->value, $row['status']);
        self::assertNotNull($row['captured_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentFailed(): void
    {
        // Given
        $other = OrderPaymentTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->failed()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::FAILED->value, $row['status']);
        self::assertNotNull($row['failed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentCancelled(): void
    {
        // Given
        $other = OrderPaymentTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->cancelled()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentVoided(): void
    {
        // Given
        $other = OrderPaymentTestFactory::new()->authorized()->store();
        $orderPayment = OrderPaymentTestFactory::new()->authorized()->cancelled()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::AUTHORIZED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentRefundInitiated(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $other = OrderPaymentTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED->value, $row['status']);
        self::assertNotNull($row['refund_initiated_at']);
        self::assertNull($row['refunded_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentRefunded(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $otherOrder = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->authorized()->captured()->refundInitiated();
        $other = $orderPayment->withOrderId($otherOrder->id->toString())->store();
        $orderPayment = $orderPayment->withOrderId($order->id->toString())->refundConfirmed()->create();

        // When
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderPaymentStatus::REFUNDED->value, $row['status']);
        self::assertNotNull($row['refund_initiated_at']);
        self::assertNotNull($row['refunded_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT order_id, amount_in_cents, reference, checkout_url, status, authorized_at, captured_at, failed_at, cancelled_at, refund_initiated_at, refunded_at FROM %s WHERE id = :id',
                DbalOrderPaymentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
