<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Infrastructure\Projection\Projector\DbalPaymentCaptureProjector;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, captured: bool|string}
 */
final class DbalPaymentCaptureProjectorTest extends AbstractIntegrationTestCase
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
        self::assertFalse((bool) $row['captured']);
    }

    #[Test]
    public function itProjectsOnPaymentCaptured(): void
    {
        // Given
        $otherBuilder = PaymentBuilder::new();
        $other = $otherBuilder->create();
        $builder = PaymentBuilder::new()->authorized()->captured();
        $payment = $builder->create();
        $this->store($other, $payment);

        // Then
        $row = $this->fetchRow($builder['orderId']);
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['captured']);

        $otherRow = $this->fetchRow($otherBuilder['orderId']);
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['captured']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT order_id, captured FROM %s WHERE order_id = :orderId', DbalPaymentCaptureProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
