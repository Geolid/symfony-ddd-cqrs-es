<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartProjector;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{shopper_id: string}
 */
final class DbalCartProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartCheckedOutIntegrationEvent(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $builder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $builder->create();

        // When
        $this->store($other, $cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['shopperId'], $row['shopper_id']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $cartId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT shopper_id FROM %s WHERE cart_id = :cartId', DbalCartProjector::TABLE),
            ['cartId' => $cartId],
        );
    }
}
