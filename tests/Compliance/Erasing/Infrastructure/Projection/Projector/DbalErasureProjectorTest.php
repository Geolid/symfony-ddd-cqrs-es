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
 * @phpstan-type Row array{status: string, requested_at: string|null}
 */
final class DbalErasureProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnErasureRequested(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $erasure = ErasureBuilder::new()->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnErasureCancelled(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $erasure = ErasureBuilder::new()->cancelled()->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::RETAINED->value, $row['status']);
        self::assertNull($row['requested_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureRequestStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnErasureApproved(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $erasure = ErasureBuilder::new()->approved()->create();

        // When
        $this->store($other, $erasure);

        // Then
        $row = $this->fetchRow($erasure->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureRequestStatus::APPROVED->value, $row['status']);

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
            \sprintf('SELECT status, requested_at FROM %s WHERE id = :id', DbalErasureProjector::TABLE),
            ['id' => $id],
        );
    }
}
