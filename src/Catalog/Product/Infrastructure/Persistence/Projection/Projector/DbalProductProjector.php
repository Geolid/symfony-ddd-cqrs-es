<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\Projection\Projector;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('catalog.product.products')]
final readonly class DbalProductProjector extends AbstractDbalProjector
{
    public const string TABLE = 'catalog_product';

    #[Subscribe(ProductListed::class)]
    public function onProductListed(ProductListed $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'label' => $event->label,
            'unit_amount_in_cents' => $event->unitAmountInCents,
            'delisted' => 0,
        ]);
    }

    #[Subscribe(ProductRepriced::class)]
    public function onProductRepriced(ProductRepriced $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['unit_amount_in_cents' => $event->unitAmountInCents],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ProductDelisted::class)]
    public function onProductDelisted(ProductDelisted $event): void
    {
        $this->connection->update(self::TABLE, ['delisted' => 1], ['id' => $event->id]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('unit_amount_in_cents', Types::INTEGER);
        $table->addColumn('delisted', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['label'], 'catalog_product_label_unique');
    }
}
