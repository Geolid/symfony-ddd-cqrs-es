<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

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
        $customer = CustomerTestFactory::new()->create();

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
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'street' => '12 rue des Lilas', 'postal_code' => '75001', 'city' => 'Paris'],
            $this->postalAddress($row['shipping_address']),
        );
        self::assertNull($row['billing_address']);
    }

    #[Test]
    public function itProjectsOnCustomerBillingAddressRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['billing_address']);
        self::assertSame(
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'street' => '8 avenue Foch', 'postal_code' => '75116', 'city' => 'Paris'],
            $this->postalAddress($row['billing_address']),
        );
        self::assertNull($row['shipping_address']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $other = CustomerTestFactory::new()->create();
        $this->store($other);
        $customer = CustomerTestFactory::new()->erased()->create();

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['customer_id']);
    }

    /**
     * @return array{first_name: string, last_name: string, street: string, postal_code: string, city: string}
     */
    private function postalAddress(string $json): array
    {
        /** @var array{first_name: string, last_name: string, street: string, postal_code: string, city: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $customerId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, shipping_address, billing_address FROM %s WHERE customer_id = :customerId',
                DbalBuyerProjector::TABLE,
            ),
            ['customerId' => $customerId],
        );
    }
}
