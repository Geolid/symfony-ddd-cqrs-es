<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Infrastructure\Projection\Projector\DbalOrderPaymentProjector;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, paid: bool|string}
 */
final class DbalOrderPaymentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnPaymentCaptured(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $other = PaymentBuilder::new()->create();
        $payment = PaymentBuilder::new()->authorized()->captured($orderId)->create();
        $this->store($other, $payment);

        // Then
        $row = $this->fetchRow($orderId);
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['paid']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT order_id, paid FROM %s WHERE order_id = :orderId', DbalOrderPaymentProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
