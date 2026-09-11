<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{cart_id: string, shopper_id: string, status: string}
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
    public function itProjectsOnCheckoutSessionConsumed(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionBuilder::new()->consumed()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CheckoutSessionStatus::CONSUMED->value, $row['status']);

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
            \sprintf('SELECT cart_id, shopper_id, status FROM %s WHERE id = :id', DbalCheckoutSessionProjector::TABLE),
            ['id' => $id],
        );
    }
}
