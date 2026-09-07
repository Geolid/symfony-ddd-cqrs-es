<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCancelled;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('finance.payment.project_payments')]
final readonly class DbalPaymentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'finance_payment';

    #[Subscribe(PaymentRequested::class)]
    public function onPaymentRequested(PaymentRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'order_id' => $event->orderId,
                'amount_in_cents' => $event->amount->cents,
                'reference' => $event->reference->value,
                'checkout_url' => $event->checkoutUrl,
                'status' => PaymentStatus::REQUESTED->value,
                'requested_at' => $event->requestedAt,
            ],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PaymentAuthorized::class)]
    public function onPaymentAuthorized(PaymentAuthorized $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => PaymentStatus::AUTHORIZED->value,
                'authorized_at' => $event->authorizedAt,
            ],
            ['id' => $event->id],
            ['authorized_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PaymentFailed::class)]
    public function onPaymentFailed(PaymentFailed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => PaymentStatus::FAILED->value,
                'failed_at' => $event->failedAt,
            ],
            ['id' => $event->id],
            ['failed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PaymentCaptured::class)]
    public function onPaymentCaptured(PaymentCaptured $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => PaymentStatus::CAPTURED->value,
                'captured_at' => $event->capturedAt,
            ],
            ['id' => $event->id],
            ['captured_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PaymentCancelled::class)]
    public function onPaymentCancelled(PaymentCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => PaymentStatus::CANCELLED->value,
                'cancelled_at' => $event->cancelledAt,
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PaymentVoided::class)]
    public function onPaymentVoided(PaymentVoided $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => PaymentStatus::CANCELLED->value,
                'cancelled_at' => $event->voidedAt,
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'sales_order_payment_order_id_idx');
        $table->addIndex(['reference'], 'sales_order_payment_reference_idx');
    }
}
