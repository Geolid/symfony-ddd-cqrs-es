<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentFailed;
use Sales\Order\Domain\Event\OrderPaymentRefundRequested;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order.order_payments')]
final readonly class DbalOrderPaymentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_payment';

    #[Subscribe(OrderPaymentRequested::class)]
    public function onOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'order_id' => $event->orderId,
            'amount_in_cents' => $event->amountInCents,
            'reference' => $event->reference,
            'checkout_url' => $event->checkoutUrl,
            'status' => OrderPaymentStatus::REQUESTED->value,
            'requested_at' => new \DateTimeImmutable($event->requestedAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(OrderPaymentAuthorized::class)]
    public function onOrderPaymentAuthorized(OrderPaymentAuthorized $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderPaymentStatus::AUTHORIZED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderPaymentFailed::class)]
    public function onOrderPaymentFailed(OrderPaymentFailed $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderPaymentStatus::FAILED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderPaymentCaptured::class)]
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderPaymentStatus::CAPTURED->value,
                'captured_at' => new \DateTimeImmutable($event->capturedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderPaymentCancelled::class)]
    public function onOrderPaymentCancelled(OrderPaymentCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderPaymentStatus::CANCELLED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderPaymentVoided::class)]
    public function onOrderPaymentVoided(OrderPaymentVoided $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderPaymentStatus::CANCELLED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderPaymentRefundRequested::class)]
    public function onOrderPaymentRefundRequested(OrderPaymentRefundRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderPaymentStatus::REFUNDING->value],
            ['id' => $event->id],
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
        $table->addColumn('reference', Types::STRING, ['length' => 64]);
        $table->addColumn('checkout_url', Types::STRING, ['length' => 2048]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('requested_at', Types::DATETIME_MUTABLE);
        $table->addColumn('captured_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'sales_order_payment_order_id_idx');
        $table->addIndex(['reference'], 'sales_order_payment_reference_idx');
    }
}
