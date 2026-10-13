<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\TaxedAmount;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{cart_id: string, customer_id: string, items: string, shipping_address: string, billing_address: string, total_excluding_tax_in_cents: int|string, total_tax_amount_in_cents: int|string, total_including_tax_in_cents: int|string, currency: string, tax_rate_basis_points: int|string, status: string}
 */
final class DbalCheckoutSessionProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpened(): void
    {
        // Given
        $other = CheckoutSessionFactory::new()->create();
        $checkoutSession = CheckoutSessionFactory::new()->create();

        // When
        $this->store($other, $checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString());
        self::assertNotFalse($row);
        self::assertSame($checkoutSession->cartId, $row['cart_id']);
        self::assertSame($checkoutSession->customerId, $row['customer_id']);
        self::assertSame(
            array_map(
                static fn (CheckoutItem $item): array => SnakeCaseKeys::from(CheckoutItemMapper::toArray($item)),
                $checkoutSession->items,
            ),
            json_decode($row['items'], true),
        );
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($checkoutSession->shippingAddress)),
            json_decode($row['shipping_address'], true),
        );
        self::assertSame(
            SnakeCaseKeys::from(PostalAddressMapper::toArray($checkoutSession->billingAddress)),
            json_decode($row['billing_address'], true),
        );
        $total = array_reduce(
            $checkoutSession->items,
            static fn (TaxedAmount $carry, CheckoutItem $item): TaxedAmount => $carry->plus($item->taxedTotal()),
            TaxedAmount::zero($checkoutSession->total->excludingTax->currency),
        );
        self::assertSame($total->excludingTax->cents, (int) $row['total_excluding_tax_in_cents']);
        self::assertSame($total->taxAmount->cents, (int) $row['total_tax_amount_in_cents']);
        self::assertSame($total->includingTax->cents, (int) $row['total_including_tax_in_cents']);
        self::assertSame($checkoutSession->total->excludingTax->currency->value, $row['currency']);
        self::assertSame($checkoutSession->items[0]->taxRate->basisPoints, (int) $row['tax_rate_basis_points']);
        self::assertSame(CheckoutSessionStatus::OPEN->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCheckoutSessionExpired(): void
    {
        // Given
        $other = CheckoutSessionFactory::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionFactory::new()->expired()->create();

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
        $other = CheckoutSessionFactory::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionFactory::new()->staled()->create();

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
        $other = CheckoutSessionFactory::new()->create();
        $this->store($other);
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();

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
            \sprintf('SELECT cart_id, customer_id, items, shipping_address, billing_address, total_excluding_tax_in_cents, total_tax_amount_in_cents, total_including_tax_in_cents, currency, tax_rate_basis_points, status FROM %s WHERE id = :id', DbalCheckoutSessionProjector::TABLE),
            ['id' => $id],
        );
    }
}
