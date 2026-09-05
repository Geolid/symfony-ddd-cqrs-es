<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Refund\Application\RefundStatus;
use Finance\Refund\Domain\Event\RefundConfirmed;
use Finance\Refund\Domain\Event\RefundFailed;
use Finance\Refund\Domain\Event\RefundInitiated;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('finance.refund.project_refunds')]
final readonly class DbalRefundProjector extends AbstractDbalProjector
{
    public const string TABLE = 'finance_refund';

    #[Subscribe(RefundInitiated::class)]
    public function onRefundInitiated(RefundInitiated $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
                'amount_in_cents' => $event->amountInCents,
                'status' => RefundStatus::INITIATED->value,
                'initiated_at' => $event->initiatedAt,
            ],
            ['initiated_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(RefundConfirmed::class)]
    public function onRefundConfirmed(RefundConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => RefundStatus::REFUNDED->value,
                'refunded_at' => $event->refundedAt,
            ],
            ['id' => $event->id],
            ['refunded_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(RefundFailed::class)]
    public function onRefundFailed(RefundFailed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => RefundStatus::FAILED->value,
                'failed_at' => $event->failedAt,
            ],
            ['id' => $event->id],
            ['failed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('payment_id', Types::STRING, ['length' => 36]);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 9]);
        $table->addColumn('initiated_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('refunded_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('failed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['payment_id'], 'finance_refund_payment_id_idx');
        $table->addIndex(['order_id'], 'finance_refund_order_id_idx');
    }
}
