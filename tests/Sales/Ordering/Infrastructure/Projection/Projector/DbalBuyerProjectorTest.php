<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     buyer_id: string,
 *     shipping_address: string|null,
 *     billing_address: string|null,
 *     erasure_requested: bool,
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
        self::assertNull($row['billing_address']);
        self::assertFalse((bool) $row['erasure_requested']);
    }

    #[Test]
    public function itProjectsOnBuyerShippingAddressDefined(): void
    {
        // Given
        $otherBuilder = BuyerBuilder::new()->shippingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = BuyerBuilder::new()->shippingAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from($builder['shippingAddress']->toArray()),
            $this->decoded($row['shipping_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from($otherBuilder['shippingAddress']->toArray()),
            $this->decoded($otherRow['shipping_address']),
        );
    }

    #[Test]
    public function itProjectsOnBuyerBillingAddressDefined(): void
    {
        // Given
        $otherBuilder = BuyerBuilder::new()->billingAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = BuyerBuilder::new()->billingAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['billing_address']);
        self::assertSame(
            SnakeCaseKeys::from($builder['billingAddress']->toArray()),
            $this->decoded($row['billing_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['billing_address']);
        self::assertSame(
            SnakeCaseKeys::from($otherBuilder['billingAddress']->toArray()),
            $this->decoded($otherRow['billing_address']),
        );
    }

    #[Test]
    public function itRemovesOnBuyerErased(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($buyer);

        // Then
        self::assertFalse($this->fetchRow($buyer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['buyer_id']);
    }

    #[Test]
    public function itProjectsOnBuyerErasureRequestedIntegrationEvent(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['erasure_requested']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['erasure_requested']);
    }

    #[Test]
    public function itProjectsOnBuyerErasureCancelledIntegrationEvent(): void
    {
        // Given
        $other = BuyerBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
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
    private function fetchRow(string $buyerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT buyer_id, shipping_address, billing_address, erasure_requested FROM %s WHERE buyer_id = :buyerId',
                DbalBuyerProjector::TABLE,
            ),
            ['buyerId' => $buyerId],
        );
    }
}
