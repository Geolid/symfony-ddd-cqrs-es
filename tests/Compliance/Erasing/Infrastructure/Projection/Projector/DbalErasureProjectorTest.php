<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Infrastructure\Projection\Projector;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Infrastructure\Projection\Projector\DbalErasureProjector;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{status: string, requested_at: string|null, cancelled_at: string|null, approved_at: string|null}
 */
final class DbalErasureProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnErasureRequested(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $builder = ErasureBuilder::new();
        $erasure = $builder->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $row['status']);
        self::assertSame($builder['requestedAt']->format(self::DATE_FORMAT), $row['requested_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnErasureCancelled(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $builder = ErasureBuilder::new()->cancelled();
        $erasure = $builder->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::CANCELLED->value, $row['status']);
        self::assertSame($builder['cancelledAt']->format(self::DATE_FORMAT), $row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnErasureApproved(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $builder = ErasureBuilder::new()->approved();
        $erasure = $builder->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::APPROVED->value, $row['status']);
        self::assertSame($builder['approvedAt']->format(self::DATE_FORMAT), $row['approved_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT status, requested_at, cancelled_at, approved_at FROM %s WHERE id = :id', DbalErasureProjector::TABLE),
            ['id' => $id],
        );
    }
}
