<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{cart_id: string, shopper_id: string, shipping_address: string, billing_address: string}
 */
final class DbalCheckoutSessionProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpenedIntegrationEvent(): void
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
        self::assertSame(SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['shippingAddress'])), json_decode($row['shipping_address'], true));
        self::assertSame(SnakeCaseKeys::from(PostalAddressMapper::toArray($builder['billingAddress'])), json_decode($row['billing_address'], true));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $checkoutSessionId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT cart_id, shopper_id, shipping_address, billing_address FROM %s WHERE checkout_session_id = :checkoutSessionId', DbalCheckoutSessionProjector::TABLE),
            ['checkoutSessionId' => $checkoutSessionId],
        );
    }
}
