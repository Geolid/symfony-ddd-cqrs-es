<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     customer_id: string,
 *     shipping_address: string|null,
 *     billing_address: string|null,
 * }
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNull($row['shipping_address']);
        self::assertNull($row['billing_address']);
    }

    #[Test]
    public function itProjectsOnCustomerShippingAddressRegistered(): void
    {
        // Given
        $shippingAddress = $this->shippingAddress();
        $customer = CustomerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            $this->primitiveAddress($shippingAddress),
            $this->postalAddress($row['shipping_address']),
        );
        self::assertNull($row['billing_address']);
    }

    #[Test]
    public function itProjectsOnCustomerBillingAddressRegistered(): void
    {
        // Given
        $billingAddress = $this->billingAddress();
        $customer = CustomerBuilder::new()
            ->billingAddressRegistered($billingAddress)
            ->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['billing_address']);
        self::assertSame(
            $this->primitiveAddress($billingAddress),
            $this->postalAddress($row['billing_address']),
        );
        self::assertNull($row['shipping_address']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $other = CustomerBuilder::new()->create();
        $this->store($other);
        $customer = CustomerBuilder::new()->erased()->create();

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['customer_id']);
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    /**
     * @return array{first_name: string, last_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function primitiveAddress(PostalAddress $address): array
    {
        return [
            'first_name' => $address->fullName->firstName,
            'last_name' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postal_code' => $address->address->postalCode,
            'city' => $address->address->city,
            'country_code' => $address->address->countryCode->value,
        ];
    }

    /**
     * @return array{first_name: string, last_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function postalAddress(string $json): array
    {
        /** @var array{first_name: string, last_name: string, street: string, postal_code: string, city: string, country_code: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $customerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT customer_id, shipping_address, billing_address FROM %s WHERE customer_id = :customerId',
                DbalBuyerProjector::TABLE,
            ),
            ['customerId' => $customerId],
        );
    }
}
