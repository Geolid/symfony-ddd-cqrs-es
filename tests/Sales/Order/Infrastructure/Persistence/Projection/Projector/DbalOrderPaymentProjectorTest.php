<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, amount_in_cents: int|string, reference: string, checkout_url: string, status: string, captured_at: ?string}
 */
final class DbalOrderPaymentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsThePaymentOnOrderPaymentRequested(): void
    {
        // When
        $orderPayment = OrderPaymentTestFactory::new()
            ->withAmountInCents(4_200)
            ->withReference('GLBX-9F3K2M1P')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->create();
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($orderPayment->orderId(), $row['order_id']);
        self::assertSame(4_200, (int) $row['amount_in_cents']);
        self::assertSame('GLBX-9F3K2M1P', $row['reference']);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $row['checkout_url']);
        self::assertSame('requested', $row['status']);
        self::assertNull($row['captured_at']);
    }

    #[Test]
    public function itProjectsTheCaptureOnOrderPaymentCaptured(): void
    {
        // When
        $orderPayment = OrderPaymentTestFactory::new()->captured()->create();
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($orderPayment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('captured', $row['status']);
        self::assertNotNull($row['captured_at']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT order_id, amount_in_cents, reference, checkout_url, status, captured_at FROM %s WHERE id = :id',
                DbalOrderPaymentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
