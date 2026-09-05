<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Refund\Infrastructure\Projection\Projector\DbalPlacedPaymentProjector;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{payment_id: string, amount_in_cents: int|string}
 */
final class DbalPlacedPaymentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnPaymentRequested(): void
    {
        // Given
        $builder = PaymentBuilder::new();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $row = $this->fetchRow($builder['orderId']);
        self::assertNotFalse($row);
        self::assertSame($payment->id->toString(), $row['payment_id']);
        self::assertSame($builder['amount']->cents, (int) $row['amount_in_cents']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT payment_id, amount_in_cents FROM %s WHERE order_id = :orderId', DbalPlacedPaymentProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
