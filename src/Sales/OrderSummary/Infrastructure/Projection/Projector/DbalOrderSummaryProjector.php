<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPaymentRequested\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\OrderSummary\Infrastructure\Projection\Transformer\OrderSummaryStatusTransformer;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

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
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'total_amount_in_cents' => $event->totalAmountInCents,
                'order_status' => 'placed',
                'placed_at' => $event->placedAt,
                'payment_status' => null,
                'shipment_status' => null,
                'status' => $this->statusTransformer->compute('placed', null, null)->value,
            ],
            ['placed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function onOrderCancelled(OrderCancelledIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'cancelled_at' => $event->cancelledAt,
        ], orderStatus: 'cancelled', types: ['cancelled_at' => Types::DATETIME_IMMUTABLE]);
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
            'paid_at' => $event->capturedAt,
        ], paymentStatus: 'captured', types: ['paid_at' => Types::DATETIME_IMMUTABLE]);
    }

    #[Subscribe(ShipmentDispatchedIntegrationEvent::class)]
    public function onShipmentDispatched(ShipmentDispatchedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'dispatched_at' => $event->dispatchedAt,
        ], shipmentStatus: 'dispatched', types: ['dispatched_at' => Types::DATETIME_IMMUTABLE]);
    }

    #[Subscribe(ShipmentManifestedIntegrationEvent::class)]
    public function onShipmentManifested(ShipmentManifestedIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'tracking_reference' => $event->trackingReference,
        ]);
    }

    #[Subscribe(ShipmentDeliveredIntegrationEvent::class)]
    public function onShipmentDelivered(ShipmentDeliveredIntegrationEvent $event): void
    {
        $this->recompute($event->orderId, [
            'delivered_at' => $event->deliveredAt,
        ], shipmentStatus: 'delivered', types: ['delivered_at' => Types::DATETIME_IMMUTABLE]);
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
        $table->addColumn('placed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('payment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('payment_amount_in_cents', Types::INTEGER, ['notnull' => false, 'default' => null]);
        $table->addColumn('payment_reference', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('payment_checkout_url', Types::STRING, ['length' => 2048, 'notnull' => false, 'default' => null]);
        $table->addColumn('paid_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('shipment_status', Types::STRING, ['length' => 10, 'notnull' => false, 'default' => null]);
        $table->addColumn('tracking_reference', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
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
     * @param array<string, mixed>  $changes
     * @param array<string, string> $types
     */
    private function recompute(
        string $orderId,
        array $changes,
        ?string $orderStatus = null,
        ?string $paymentStatus = null,
        ?string $shipmentStatus = null,
        array $types = [],
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
            $types,
        );
    }
}
