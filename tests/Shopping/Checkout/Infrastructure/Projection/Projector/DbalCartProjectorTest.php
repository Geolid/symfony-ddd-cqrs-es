<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartProjector;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string}
 */
final class DbalCartProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartStarted(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $builder = CartBuilder::new();
        $cart = $builder->create();

        // When
        $this->store($other, $cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['customerId'], $row['customer_id']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT customer_id FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );
    }
}
