<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentFailed;
use Sales\Order\Domain\Event\OrderPaymentRefunded;
use Sales\Order\Domain\Event\OrderPaymentRefundInitiated;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order.project_order_payments')]
final readonly class DbalOrderPaymentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_payment';

    #[Subscribe(OrderPaymentRequested::class)]
    public function onOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'order_id' => $event->orderId,
                'amount_in_cents' => $event->amountInCents,
                'reference' => $event->reference,
                'checkout_url' => $event->checkoutUrl,
                'status' => OrderPaymentStatus::REQUESTED->value,
                'requested_at' => new \DateTimeImmutable($event->requestedAt),
            ],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentAuthorized::class)]
    public function onOrderPaymentAuthorized(OrderPaymentAuthorized $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::AUTHORIZED->value,
                'authorized_at' => new \DateTimeImmutable($event->authorizedAt),
            ],
            ['id' => $event->id],
            ['authorized_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentFailed::class)]
    public function onOrderPaymentFailed(OrderPaymentFailed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::FAILED->value,
                'failed_at' => new \DateTimeImmutable($event->failedAt),
            ],
            ['id' => $event->id],
            ['failed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentCaptured::class)]
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::CAPTURED->value,
                'captured_at' => new \DateTimeImmutable($event->capturedAt),
            ],
            ['id' => $event->id],
            ['captured_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentCancelled::class)]
    public function onOrderPaymentCancelled(OrderPaymentCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::CANCELLED->value,
                'cancelled_at' => new \DateTimeImmutable($event->cancelledAt),
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentVoided::class)]
    public function onOrderPaymentVoided(OrderPaymentVoided $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::CANCELLED->value,
                'cancelled_at' => new \DateTimeImmutable($event->voidedAt),
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentRefundInitiated::class)]
    public function onOrderPaymentRefundInitiated(OrderPaymentRefundInitiated $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::REFUND_INITIATED->value,
                'refund_initiated_at' => new \DateTimeImmutable($event->initiatedAt),
            ],
            ['id' => $event->id],
            ['refund_initiated_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPaymentRefunded::class)]
    public function onOrderPaymentRefunded(OrderPaymentRefunded $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::REFUNDED->value,
                'refunded_at' => new \DateTimeImmutable($event->refundedAt),
            ],
            ['id' => $event->id],
            ['refunded_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('amount_in_cents', Types::INTEGER);
        $table->addColumn('reference', Types::STRING, ['length' => PaymentReference::MAX_LENGTH]);
        $table->addColumn('checkout_url', Types::STRING, ['length' => 2048]);
        $table->addColumn('status', Types::STRING, ['length' => 17]);
        $table->addColumn('requested_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('authorized_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('captured_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('failed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('refund_initiated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('refunded_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'sales_order_payment_order_id_idx');
        $table->addIndex(['reference'], 'sales_order_payment_reference_idx');
    }
}
