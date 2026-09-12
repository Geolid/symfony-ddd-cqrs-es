<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCheckoutSessionLineProjector;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_price_in_cents: int|string, quantity: int|string}
 */
final class DbalCheckoutSessionLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpenedIntegrationEvent(): void
    {
        // Given
        $otherBuilder = CheckoutSessionBuilder::new();
        $other = $otherBuilder->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();

        // When
        $this->store($other, $checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString(), $builder['lines'][0]['productId']);
        self::assertNotFalse($row);
        self::assertSame($builder['lines'][0]['label'], $row['label']);
        self::assertSame($builder['lines'][0]['unitPriceInCents'], (int) $row['unit_price_in_cents']);
        self::assertSame($builder['lines'][0]['quantity'], (int) $row['quantity']);

        $otherRow = $this->fetchRow($other->id->toString(), $otherBuilder['lines'][0]['productId']);
        self::assertNotFalse($otherRow);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $checkoutSessionId, string $productId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT label, unit_price_in_cents, quantity FROM %s WHERE checkout_session_id = :checkoutSessionId AND product_id = :productId',
                DbalCheckoutSessionLineProjector::TABLE,
            ),
            ['checkoutSessionId' => $checkoutSessionId, 'productId' => $productId],
        );
    }
}
