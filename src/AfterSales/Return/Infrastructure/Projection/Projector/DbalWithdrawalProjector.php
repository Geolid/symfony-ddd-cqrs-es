<?php

declare(strict_types=1);

namespace AfterSales\Return\Infrastructure\Projection\Projector;

use AfterSales\Return\Application\WithdrawalStatus;
use AfterSales\Return\Domain\Event\WithdrawalApproved;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Return\Domain\Event\WithdrawalRejected;
use AfterSales\Return\Domain\Event\WithdrawalRequested;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('after_sales.return.project_withdrawals')]
final readonly class DbalWithdrawalProjector extends AbstractDbalProjector
{
    public const string TABLE = 'after_sales_return_withdrawal';

    #[Subscribe(WithdrawalRequested::class)]
    public function onWithdrawalRequested(WithdrawalRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'order_id' => $event->orderId,
                'buyer_id' => $event->buyerId,
                'status' => WithdrawalStatus::REQUESTED->value,
                'requested_at' => $event->requestedAt,
            ],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(WithdrawalReceived::class)]
    public function onWithdrawalReceived(WithdrawalReceived $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => WithdrawalStatus::RECEIVED->value,
                'received_at' => $event->receivedAt,
            ],
            ['id' => $event->id],
            ['received_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(WithdrawalApproved::class)]
    public function onWithdrawalApproved(WithdrawalApproved $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => WithdrawalStatus::APPROVED->value,
                'approved_at' => $event->approvedAt,
            ],
            ['id' => $event->id],
            ['approved_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(WithdrawalRejected::class)]
    public function onWithdrawalRejected(WithdrawalRejected $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => WithdrawalStatus::REJECTED->value,
                'rejected_at' => $event->rejectedAt,
            ],
            ['id' => $event->id],
            ['rejected_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('requested_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('received_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('approved_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('rejected_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'after_sales_return_withdrawal_order_id_idx');
    }
}
