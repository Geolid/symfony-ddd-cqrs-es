<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payment\Application\IntegrationEvent\PaymentRequested\PaymentRequestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('finance.refund.project_placed_payments')]
final readonly class DbalPlacedPaymentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'finance_refund_placed_payment';

    #[Subscribe(PaymentRequestedIntegrationEvent::class)]
    public function onPaymentRequested(PaymentRequestedIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'payment_id' => $event->paymentId,
                'amount_in_cents' => $event->amountInCents,
            ],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('payment_id', Types::STRING, ['length' => 36]);
        $table->addColumn('amount_in_cents', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
    }
}
