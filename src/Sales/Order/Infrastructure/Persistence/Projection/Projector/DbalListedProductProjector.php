<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Catalog\Product\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\IntegrationEvent\ProductListed\ProductListedIntegrationEvent;
use Catalog\Product\Application\IntegrationEvent\ProductRepriced\ProductRepricedIntegrationEvent;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order.listed_products')]
final readonly class DbalListedProductProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_listed_products';

    #[Subscribe(ProductListedIntegrationEvent::class)]
    public function onProductListed(ProductListedIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'product_id' => $event->productId,
            'label' => $event->label,
            'unit_amount_in_cents' => $event->unitAmountInCents,
        ]);
    }

    #[Subscribe(ProductRepricedIntegrationEvent::class)]
    public function onProductRepriced(ProductRepricedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['unit_amount_in_cents' => $event->unitAmountInCents],
            ['product_id' => $event->productId],
        );
    }

    #[Subscribe(ProductDelistedIntegrationEvent::class)]
    public function onProductDelisted(ProductDelistedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['product_id' => $event->productId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('product_id', Types::STRING, ['length' => 36]);
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('unit_amount_in_cents', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('product_id'))
                ->create(),
        );
    }
}
