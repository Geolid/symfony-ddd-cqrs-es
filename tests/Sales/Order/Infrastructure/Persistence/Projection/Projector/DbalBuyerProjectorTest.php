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
 *     shipping_first_name: string|null,
 *     shipping_last_name: string|null,
 *     shipping_street: string|null,
 *     shipping_postal_code: string|null,
 *     shipping_city: string|null,
 *     billing_first_name: string|null,
 *     billing_last_name: string|null,
 *     billing_street: string|null,
 *     billing_postal_code: string|null,
 *     billing_city: string|null,
 * }
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheBuyerOnCustomerRegistered(): void
    {
        // When
        $customer = CustomerTestFactory::new()->store();

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertNull($row['shipping_street']);
        self::assertNull($row['billing_street']);
    }

    #[Test]
    public function itProjectsTheShippingAddressOnCustomerShippingAddressRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->store();

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('12 rue des Lilas', $row['shipping_street']);
        self::assertNull($row['billing_street']);
    }

    #[Test]
    public function itProjectsTheBillingAddressOnCustomerBillingAddressRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->store();

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('8 avenue Foch', $row['billing_street']);
        self::assertNull($row['shipping_street']);
    }

    #[Test]
    public function itProjectsTheRedactionOnCustomerErased(): void
    {
        // Given
        $other = CustomerTestFactory::new()->store();

        // When
        $customer = CustomerTestFactory::new()->erased()->store();

        // Then
        self::assertFalse($this->fetchRow($customer->id()->toString()));

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id()->toString(), $otherRow['customer_id']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $customerId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, shipping_first_name, shipping_last_name, shipping_street, shipping_postal_code, shipping_city, billing_first_name, billing_last_name, billing_street, billing_postal_code, billing_city FROM %s WHERE customer_id = :customerId',
                DbalBuyerProjector::TABLE,
            ),
            ['customerId' => $customerId],
        );
    }
}
