<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use Finance\Payment\Application\IntegrationEvent\PaymentRequested\PaymentRequestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('fulfilment.shipping.project_placed_payments')]
final readonly class DbalPaymentCaptureProjector extends AbstractDbalProjector
{
    public const string TABLE = 'fulfilment_shipping_payment_capture';

    #[Subscribe(PaymentRequestedIntegrationEvent::class)]
    public function onPaymentRequested(PaymentRequestedIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'captured' => false,
            ],
            ['captured' => Types::BOOLEAN],
        );
    }

    #[Subscribe(PaymentCapturedIntegrationEvent::class)]
    public function onPaymentCaptured(PaymentCapturedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['captured' => true],
            ['order_id' => $event->orderId],
            ['captured' => Types::BOOLEAN],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('captured', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
    }
}
