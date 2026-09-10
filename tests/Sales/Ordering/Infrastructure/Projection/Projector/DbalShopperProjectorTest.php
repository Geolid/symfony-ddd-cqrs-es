<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalShopperProjector;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     shopper_id: string,
 *     shipping_address: string|null,
 *     billing_address: string|null,
 *     erasure_requested: bool,
 * }
 */
final class DbalShopperProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnShopperRegistered(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertNull($row['shipping_address']);
        self::assertNull($row['billing_address']);
        self::assertFalse((bool) $row['erasure_requested']);
    }

    #[Test]
    public function itProjectsOnShopperShippingAddressDefined(): void
    {
        // Given
        $otherBuilder = ShopperBuilder::new()->shippingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = ShopperBuilder::new()->shippingAddressDefined();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
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
    public function itProjectsOnShopperBillingAddressDefined(): void
    {
        // Given
        $otherBuilder = ShopperBuilder::new()->billingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = ShopperBuilder::new()->billingAddressDefined();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
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
    public function itRemovesOnShopperErased(): void
    {
        // Given
        $other = ShopperBuilder::new()->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($shopper);

        // Then
        self::assertFalse($this->fetchRow($shopper->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['shopper_id']);
    }

    #[Test]
    public function itProjectsOnShopperErasureRequestedIntegrationEvent(): void
    {
        // Given
        $other = ShopperBuilder::new()->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['erasure_requested']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['erasure_requested']);
    }

    #[Test]
    public function itProjectsOnShopperErasureCancelledIntegrationEvent(): void
    {
        // Given
        $other = ShopperBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['erasure_requested']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['erasure_requested']);
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function decoded(string $json): array
    {
        /** @var array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $shopperId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT shopper_id, shipping_address, billing_address, erasure_requested FROM %s WHERE shopper_id = :shopperId',
                DbalShopperProjector::TABLE,
            ),
            ['shopperId' => $shopperId],
        );
    }
}
