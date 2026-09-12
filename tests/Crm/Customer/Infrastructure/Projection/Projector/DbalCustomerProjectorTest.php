<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Projection\Projector;

use Crm\Customer\Infrastructure\Projection\Projector\DbalCustomerProjector;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{first_name: string, last_name: string, email: string, registered_at: string, shipping_address: string|null, billing_address: string|null, erasure_status: string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $builder = CustomerBuilder::new();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['firstName']->value, $row['first_name']);
        self::assertSame($builder['lastName']->value, $row['last_name']);
        self::assertSame($builder['email']->value, $row['email']);
        self::assertSame($builder['registeredAt']->format('Y-m-d H:i:s'), $row['registered_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnCustomerShippingAddressDefined(): void
    {
        // Given
        $otherBuilder = CustomerBuilder::new()->shippingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = CustomerBuilder::new()->shippingAddressDefined();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['shippingAddress'])),
            $this->decoded($row['shipping_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($otherBuilder['shippingAddress'])),
            $this->decoded($otherRow['shipping_address']),
        );
    }

    #[Test]
    public function itProjectsOnCustomerBillingAddressDefined(): void
    {
        // Given
        $otherBuilder = CustomerBuilder::new()->billingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = CustomerBuilder::new()->billingAddressDefined();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['billing_address']);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['billingAddress'])),
            $this->decoded($row['billing_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['billing_address']);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($otherBuilder['billingAddress'])),
            $this->decoded($otherRow['billing_address']),
        );
    }

    #[Test]
    public function itProjectsOnCustomerErasureRequested(): void
    {
        // Given
        $other = CustomerBuilder::new()->create();
        $this->store($other);
        $customer = CustomerBuilder::new()->erasureRequested()->create();

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
        $other = CustomerBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $customer = CustomerBuilder::new()->erasureRequested()->erasureCancelled()->create();

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
        $otherBuilder = CustomerBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);
        $customer = CustomerBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['email']->value, $otherRow['email']);
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
                'SELECT first_name, last_name, email, registered_at, shipping_address, billing_address, erasure_status FROM %s WHERE id = :id',
                DbalCustomerProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
