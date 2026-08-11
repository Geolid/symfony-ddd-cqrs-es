<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order_summary.order_summary_lines')]
final readonly class DbalOrderSummaryLineProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_summary_line';

    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        foreach ($event->lines as $position => $line) {
            $this->connection->insert(self::TABLE, [
                'order_id' => $event->orderId,
                'position' => $position,
                'label' => $line['label'],
                'quantity' => $line['quantity'],
                'unit_amount_in_cents' => $line['unitAmountInCents'],
            ]);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('position', Types::INTEGER);
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('quantity', Types::INTEGER);
        $table->addColumn('unit_amount_in_cents', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(
                    UnqualifiedName::unquoted('order_id'),
                    UnqualifiedName::unquoted('position'),
                )
                ->create(),
        );
        $table->addIndex(['order_id'], 'sales_order_summary_line_order_id_idx');
    }
}
