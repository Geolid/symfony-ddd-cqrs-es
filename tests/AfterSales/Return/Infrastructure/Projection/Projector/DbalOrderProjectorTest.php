<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Projector;

use AfterSales\Return\Infrastructure\Projection\Projector\DbalOrderProjector;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, shipping_address: string, delivered_at: string}
 */
final class DbalOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderDelivered(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        $shippingAddress = $this->postalAddress($row['shipping_address']);
        $expectedShippingAddress = $this->toAddressData($builder['shippingAddress']->toArray());
        self::assertSame($builder['customerId'], $row['customer_id']);
        self::assertSame($expectedShippingAddress, $shippingAddress);
        self::assertNotNull($row['delivered_at']);
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function toAddressData(array $address): array
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
    private function postalAddress(string $json): array
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
            \sprintf('SELECT customer_id, shipping_address, delivered_at FROM %s WHERE order_id = :orderId', DbalOrderProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
