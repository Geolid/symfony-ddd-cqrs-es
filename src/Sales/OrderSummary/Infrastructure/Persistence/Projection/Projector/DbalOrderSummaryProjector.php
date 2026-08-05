<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentTrackingReferenceAssignedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Transformer\OrderSummaryStatusTransformer;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.order_summary.order_summaries')]
final readonly class DbalOrderSummaryProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_summary';

    public function __construct(
        Connection $connection,
        private OrderSummaryStatusTransformer $statusTransformer,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
            'total_amount_in_cents' => $event->totalAmountInCents,
            'order_status' => 'placed',
            'placed_at' => new \DateTimeImmutable($event->placedAt)->format('Y-m-d H:i:s'),
            'payment_status' => null,
            'shipment_status' => null,
            'status' => $this->statusTransformer->compute('placed', null, null)->value,
        ]);
    }

    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function onOrderCancelled(OrderCancelledIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'cancelled_at' => new \DateTimeImmutable($event->cancelledAt)->format('Y-m-d H:i:s'),
        ], orderStatus: 'cancelled');
    }

    #[Subscribe(OrderPaymentRequestedIntegrationEvent::class)]
    public function onOrderPaymentRequested(OrderPaymentRequestedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'payment_amount_in_cents' => $event->amountInCents,
            'payment_reference' => $event->reference,
            'payment_checkout_url' => $event->checkoutUrl,
        ], paymentStatus: 'requested');
    }

    #[Subscribe(OrderPaymentCapturedIntegrationEvent::class)]
    public function onOrderPaymentCaptured(OrderPaymentCapturedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'paid_at' => new \DateTimeImmutable($event->capturedAt)->format('Y-m-d H:i:s'),
        ], paymentStatus: 'captured');
    }

    #[Subscribe(ShipmentDispatchedIntegrationEvent::class)]
    public function onShipmentDispatched(ShipmentDispatchedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'dispatched_at' => new \DateTimeImmutable($event->dispatchedAt)->format('Y-m-d H:i:s'),
        ], shipmentStatus: 'dispatched');
    }

    #[Subscribe(ShipmentTrackingReferenceAssignedIntegrationEvent::class)]
    public function onShipmentTrackingReferenceAssigned(ShipmentTrackingReferenceAssignedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'tracking_reference' => $event->trackingReference,
        ]);
    }

    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function onShipmentDelivered(ShipmentDeliveredIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'delivered_at' => new \DateTimeImmutable($event->deliveredAt)->format('Y-m-d H:i:s'),
        ], shipmentStatus: 'delivered');
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('order_status', Types::STRING, ['length' => 10]);
        $table->addColumn('placed_at', Types::DATETIME_MUTABLE);
        $table->addColumn('cancelled_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('payment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('payment_amount_in_cents', Types::INTEGER, ['notnull' => false, 'default' => null]);
        $table->addColumn('payment_reference', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('payment_checkout_url', Types::STRING, ['length' => 2048, 'notnull' => false, 'default' => null]);
        $table->addColumn('paid_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('shipment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('tracking_reference', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
        $table->addIndex(['customer_id'], 'sales_order_summary_customer_id_idx');
        $table->addIndex(['status'], 'sales_order_summary_status_idx');
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function recompute(
        string $orderId,
        array $changes,
        ?string $orderStatus = null,
        ?string $paymentStatus = null,
        ?string $shipmentStatus = null,
    ): void {
        /** @var array{order_status: string, payment_status: string|null, shipment_status: string|null}|false $row */
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT order_status, payment_status, shipment_status FROM %s WHERE order_id = :orderId', self::TABLE),
            ['orderId' => $orderId],
        );

        if (false === $row) {
            // The order fact (OrderPlacedIntegrationEvent) hasn't been projected yet for this order —
            // nothing to enrich. Causally this can't happen outside a test fixture scoped to a single BC.
            return;
        }

        $orderStatus ??= $row['order_status'];
        $paymentStatus ??= $row['payment_status'];
        $shipmentStatus ??= $row['shipment_status'];

        $this->connection->update(
            self::TABLE,
            [
                ...$changes,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'shipment_status' => $shipmentStatus,
                'status' => $this->statusTransformer->compute($orderStatus, $paymentStatus, $shipmentStatus)->value,
            ],
            ['order_id' => $orderId],
        );
    }
}
