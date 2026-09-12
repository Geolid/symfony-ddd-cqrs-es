<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{cart_id: string, shopper_id: string, shipping_address: string, billing_address: string, total_amount_in_cents: int|string, status: string}
 */
final class DbalCheckoutSessionProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpened(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();

        // When
        $this->store($other, $checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['cartId'], $row['cart_id']);
        self::assertSame($builder['shopperId'], $row['shopper_id']);
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['shippingAddress'])),
            json_decode($row['shipping_address'], true),
        );
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['billingAddress'])),
            json_decode($row['billing_address'], true),
        );
        $totalAmountInCents = array_reduce(
            $builder['items'],
            static fn (Money $carry, CheckoutItem $item): Money => $carry->plus($item->subtotal()),
            Money::fromCents(0),
        )->cents;
        self::assertSame($totalAmountInCents, (int) $row['total_amount_in_cents']);
        self::assertSame(CheckoutSessionStatus::OPEN->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCheckoutSessionExpired(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionBuilder::new()->expired()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CheckoutSessionStatus::EXPIRED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CheckoutSessionStatus::OPEN->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnCheckoutSessionStaled(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionBuilder::new()->staled()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CheckoutSessionStatus::STALE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CheckoutSessionStatus::OPEN->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnCheckoutSessionCompleted(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionBuilder::new()->completed()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CheckoutSessionStatus::COMPLETED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CheckoutSessionStatus::OPEN->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT cart_id, shopper_id, shipping_address, billing_address, total_amount_in_cents, status FROM %s WHERE id = :id', DbalCheckoutSessionProjector::TABLE),
            ['id' => $id],
        );
    }
}
