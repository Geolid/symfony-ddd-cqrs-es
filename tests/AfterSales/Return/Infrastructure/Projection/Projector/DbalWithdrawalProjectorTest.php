<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Projector;

use AfterSales\Return\Application\WithdrawalStatus;
use AfterSales\Return\Infrastructure\Projection\Projector\DbalWithdrawalProjector;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, buyer_id: string, status: string, received_at: ?string, approved_at: ?string, rejected_at: ?string}
 */
final class DbalWithdrawalProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnWithdrawalRequested(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();

        // When
        $this->store($withdrawal);

        // Then
        $row = $this->fetchRow($withdrawal->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['orderId'], $row['order_id']);
        self::assertSame($builder['buyerId'], $row['buyer_id']);
        self::assertSame(WithdrawalStatus::REQUESTED->value, $row['status']);
        self::assertNull($row['received_at']);
        self::assertNull($row['approved_at']);
        self::assertNull($row['rejected_at']);
    }

    #[Test]
    public function itProjectsOnWithdrawalReceived(): void
    {
        // Given
        $other = WithdrawalBuilder::new()->create();
        $this->store($other);
        $withdrawal = WithdrawalBuilder::new()->received()->create();

        // When
        $this->store($withdrawal);

        // Then
        $row = $this->fetchRow($withdrawal->id->toString());
        self::assertNotFalse($row);
        self::assertSame(WithdrawalStatus::RECEIVED->value, $row['status']);
        self::assertNotNull($row['received_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(WithdrawalStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnWithdrawalApproved(): void
    {
        // Given
        $other = WithdrawalBuilder::new()->received()->create();
        $this->store($other);
        $withdrawal = WithdrawalBuilder::new()->received()->approved()->create();

        // When
        $this->store($withdrawal);

        // Then
        $row = $this->fetchRow($withdrawal->id->toString());
        self::assertNotFalse($row);
        self::assertSame(WithdrawalStatus::APPROVED->value, $row['status']);
        self::assertNotNull($row['approved_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(WithdrawalStatus::RECEIVED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnWithdrawalRejected(): void
    {
        // Given
        $other = WithdrawalBuilder::new()->received()->create();
        $this->store($other);
        $withdrawal = WithdrawalBuilder::new()->received()->rejected()->create();

        // When
        $this->store($withdrawal);

        // Then
        $row = $this->fetchRow($withdrawal->id->toString());
        self::assertNotFalse($row);
        self::assertSame(WithdrawalStatus::REJECTED->value, $row['status']);
        self::assertNotNull($row['rejected_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(WithdrawalStatus::RECEIVED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT order_id, buyer_id, status, received_at, approved_at, rejected_at FROM %s WHERE id = :id', DbalWithdrawalProjector::TABLE),
            ['id' => $id],
        );
    }
}
