<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Payment\Infrastructure\Projection\Projector\DbalPlacedOrderProjector;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{amount_in_cents: int, billing_address: string, cancelled: bool}
 */
final class DbalPlacedOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // Given
        $builder = OrderBuilder::new();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->totalAmountInCents($builder['lines']), $row['amount_in_cents']);
        self::assertSame($this->postalAddress($builder['billingAddress']->toArray()), $this->decodedAddress($row['billing_address']));
        self::assertFalse((bool) $row['cancelled']);
    }

    #[Test]
    public function itProjectsOnOrderCancelled(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['cancelled']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['cancelled']);
    }

    /**
     * @param list<OrderLine> $lines
     */
    private function totalAmountInCents(array $lines): int
    {
        return array_sum(array_map(static fn (OrderLine $line): int => $line->total()->cents, $lines));
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function postalAddress(array $address): array
    {
        return [
            'recipient_name' => $address['recipientName'],
            'street' => $address['street'],
            'postal_code' => $address['postalCode'],
            'city' => $address['city'],
            'country_code' => $address['countryCode'],
        ];
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function decodedAddress(string $json): array
    {
        /** @var array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT amount_in_cents, billing_address, cancelled FROM %s WHERE order_id = :orderId', DbalPlacedOrderProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
