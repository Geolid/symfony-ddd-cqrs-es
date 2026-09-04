<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     buyer_id: string,
 *     shipping_address: string|null,
 * }
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnBuyerRegistered(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNull($row['shipping_address']);
    }

    #[Test]
    public function itProjectsOnBuyerShippingAddressRegistered(): void
    {
        // Given
        $otherShippingAddress = PostalAddress::of('John Smith', Address::of('5 rue de la République', '69001', 'Lyon', 'FR'));
        $other = BuyerBuilder::new()
            ->shippingAddressRegistered($otherShippingAddress)
            ->create();
        $this->store($other);
        $shippingAddress = $this->shippingAddress();
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            $this->primitiveAddress($shippingAddress),
            $this->postalAddress($row['shipping_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['shipping_address']);
        self::assertSame(
            $this->primitiveAddress($otherShippingAddress),
            $this->postalAddress($otherRow['shipping_address']),
        );
    }

    #[Test]
    public function itRemovesOnBuyerErased(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erased()->create();

        // When
        $this->store($buyer);

        // Then
        self::assertFalse($this->fetchRow($buyer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['buyer_id']);
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function primitiveAddress(PostalAddress $address): array
    {
        return [
            'recipient_name' => $address->recipientName,
            'street' => $address->address->street,
            'postal_code' => $address->address->postalCode,
            'city' => $address->address->city,
            'country_code' => $address->address->countryCode->value,
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
    private function fetchRow(string $buyerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT buyer_id, shipping_address FROM %s WHERE buyer_id = :buyerId',
                DbalBuyerProjector::TABLE,
            ),
            ['buyerId' => $buyerId],
        );
    }
}
