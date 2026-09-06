<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Projector;

use AfterSales\Return\Infrastructure\Projection\Projector\DbalDeliveredOrderProjector;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{buyer_id: string, shipping_address: string, delivered_at: string}
 */
final class DbalDeliveredOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderDelivered(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['buyerId'], $row['buyer_id']);
        self::assertSame($this->postalAddress($builder['shippingAddress']->toArray()), $this->decodedPostalAddress($row['shipping_address']));
        self::assertNotNull($row['delivered_at']);
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
    private function decodedPostalAddress(string $json): array
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
            \sprintf('SELECT buyer_id, shipping_address, delivered_at FROM %s WHERE order_id = :orderId', DbalDeliveredOrderProjector::TABLE),
            ['orderId' => $orderId],
        );
    }
}
