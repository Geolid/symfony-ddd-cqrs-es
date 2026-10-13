<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Projection\Projector;

use Crm\Customer\Infrastructure\Projection\Projector\DbalCustomerProjector;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{registered_at: string, shipping_address: string|null, billing_address: string|null, erasure_status: string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $customer = CustomerFactory::new()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customer->registeredAt->format('Y-m-d H:i:s'), $row['registered_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnCustomerShippingAddressDefined(): void
    {
        // Given
        $other = CustomerFactory::new()->shippingAddressDefined()->create();
        $this->store($other);
        $customer = CustomerFactory::new()->shippingAddressDefined()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        \assert(null !== $customer->shippingAddress);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($customer->shippingAddress)),
            $this->decoded($row['shipping_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['shipping_address']);
        \assert(null !== $other->shippingAddress);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($other->shippingAddress)),
            $this->decoded($otherRow['shipping_address']),
        );
    }

    #[Test]
    public function itProjectsOnCustomerBillingAddressDefined(): void
    {
        // Given
        $other = CustomerFactory::new()->billingAddressDefined()->create();
        $this->store($other);
        $customer = CustomerFactory::new()->billingAddressDefined()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['billing_address']);
        \assert(null !== $customer->billingAddress);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($customer->billingAddress)),
            $this->decoded($row['billing_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['billing_address']);
        \assert(null !== $other->billingAddress);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($other->billingAddress)),
            $this->decoded($otherRow['billing_address']),
        );
    }

    #[Test]
    public function itProjectsOnCustomerErasureRequested(): void
    {
        // Given
        $other = CustomerFactory::new()->create();
        $this->store($other);
        $customer = CustomerFactory::new()->erasureRequested()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::REQUESTED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnCustomerErasureCancelled(): void
    {
        // Given
        $other = CustomerFactory::new()->erasureRequested()->create();
        $this->store($other);
        $customer = CustomerFactory::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::REQUESTED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $other = CustomerFactory::new()->create();
        $this->store($other);
        $customer = CustomerFactory::new()->erasureRequested()->erased()->create();

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return array{recipient_name: string, address: array{street: string, postal_code: string, city: string, country_code: string}}
     */
    private function decoded(string $json): array
    {
        /** @var array{recipient_name: string, address: array{street: string, postal_code: string, city: string, country_code: string}} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT registered_at, shipping_address, billing_address, erasure_status FROM %s WHERE id = :id',
                DbalCustomerProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
