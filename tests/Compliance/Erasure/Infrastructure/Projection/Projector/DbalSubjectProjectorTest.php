<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Infrastructure\Projection\Projector;

use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Erasure\Infrastructure\Projection\Projector\DbalSubjectProjector;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{status: string, requested_at: string|null}
 */
final class DbalSubjectProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnSubjectRegistered(): void
    {
        // Given
        $id = SubjectId::fromString(Uuid::uuid7()->toString());
        $reference = HoldReference::for('sales.order.order', Uuid::uuid7()->toString());
        $subject = Subject::place($id, $reference, Clock::get()->now());

        // When
        $this->store($subject);

        // Then
        $row = $this->fetchRow($id->toString());
        self::assertNotFalse($row);
        self::assertSame(SubjectStatus::RETAINED->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnSubjectErasureRequested(): void
    {
        // Given
        $subject = SubjectBuilder::new()->create();

        // When
        $this->store($subject);

        // Then
        $row = $this->fetchRow($subject->id->toString());
        self::assertNotFalse($row);
        self::assertSame(SubjectStatus::ERASING->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnSubjectErasureCancelled(): void
    {
        // Given
        $other = SubjectBuilder::new()->create();
        $this->store($other);
        $subject = SubjectBuilder::new()->cancelled()->create();

        // When
        $this->store($subject);

        // Then
        $row = $this->fetchRow($subject->id->toString());
        self::assertNotFalse($row);
        self::assertSame(SubjectStatus::RETAINED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(SubjectStatus::ERASING->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnSubjectErased(): void
    {
        // Given
        $other = SubjectBuilder::new()->create();
        $this->store($other);
        $subject = SubjectBuilder::new()->released()->create();

        // When
        $this->store($subject);

        // Then
        $row = $this->fetchRow($subject->id->toString());
        self::assertNotFalse($row);
        self::assertSame(SubjectStatus::ERASED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(SubjectStatus::ERASING->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT status, requested_at FROM %s WHERE id = :id', DbalSubjectProjector::TABLE),
            ['id' => $id],
        );
    }
}
