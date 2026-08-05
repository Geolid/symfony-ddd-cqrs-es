<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\OrderTracking\Application\Service\OrderTrackingStatusResolver;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.order_tracking.order_trackings')]
final readonly class DbalOrderTrackingProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_tracking';

    public function __construct(
        \Doctrine\DBAL\Connection $connection,
        private OrderTrackingStatusResolver $statusResolver,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $orderStatus = 'placed';

        $this->connection->insert(self::TABLE, [
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
            'order_status' => $orderStatus,
            'payment_status' => null,
            'shipment_status' => null,
            'status' => $this->statusResolver->resolve($orderStatus, null, null),
            'placed_at' => new \DateTimeImmutable($event->placedAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function onOrderCancelled(OrderCancelledIntegrationEvent $event): void
    {
        $this->updateOrderStatus($event->orderId, 'cancelled');
    }

    #[Subscribe(OrderPaymentRequestedIntegrationEvent::class)]
    public function onOrderPaymentRequested(OrderPaymentRequestedIntegrationEvent $event): void
    {
        $this->updatePaymentStatus($event->orderId, 'requested');
    }

    #[Subscribe(OrderPaymentCapturedIntegrationEvent::class)]
    public function onOrderPaymentCaptured(OrderPaymentCapturedIntegrationEvent $event): void
    {
        $this->updatePaymentStatus($event->orderId, 'captured');
    }

    #[Subscribe(ShipmentDispatchedIntegrationEvent::class)]
    public function onShipmentDispatched(ShipmentDispatchedIntegrationEvent $event): void
    {
        $this->updateShipmentStatus($event->orderId, 'dispatched');
    }

    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function onShipmentDelivered(ShipmentDeliveredIntegrationEvent $event): void
    {
        $this->updateShipmentStatus($event->orderId, 'delivered');
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('order_status', Types::STRING, ['length' => 10]);
        $table->addColumn('payment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('shipment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addColumn('placed_at', Types::DATETIME_MUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
        $table->addIndex(['customer_id'], 'sales_order_tracking_customer_id_idx');
        $table->addIndex(['status'], 'sales_order_tracking_status_idx');
    }

    private function updateOrderStatus(string $orderId, string $orderStatus): void
    {
        $this->recompute($orderId, orderStatus: $orderStatus);
    }

    private function updatePaymentStatus(string $orderId, string $paymentStatus): void
    {
        $this->recompute($orderId, paymentStatus: $paymentStatus);
    }

    private function updateShipmentStatus(string $orderId, string $shipmentStatus): void
    {
        $this->recompute($orderId, shipmentStatus: $shipmentStatus);
    }

    private function recompute(
        string $orderId,
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
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'shipment_status' => $shipmentStatus,
                'status' => $this->statusResolver->resolve($orderStatus, $paymentStatus, $shipmentStatus),
            ],
            ['order_id' => $orderId],
        );
    }
}
