<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Payer\Infrastructure\Projection\Projector\DbalPayerProjector;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{registered_at: string}
 */
final class DbalPayerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnPayerRegistered(): void
    {
        // Given
        $builder = PayerBuilder::new();
        $payer = $builder->create();

        // When
        $this->store($payer);

        // Then
        $row = $this->fetchRow($payer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['registeredAt']->format('Y-m-d H:i:s'), $row['registered_at']);
    }

    #[Test]
    public function itRemovesOnPayerErased(): void
    {
        // Given
        $other = PayerBuilder::new()->create();
        $this->store($other);
        $payer = PayerBuilder::new()->erased()->create();

        // When
        $this->store($payer);

        // Then
        self::assertFalse($this->fetchRow($payer->id->toString()));

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
            \sprintf('SELECT registered_at FROM %s WHERE id = :id', DbalPayerProjector::TABLE),
            ['id' => $id],
        );
    }
}
