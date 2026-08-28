<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Projection\Projector;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Domain\ValueObject\Label;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\Projector\Projector;

#[Projector('catalog.product.project_products')]
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
        $this->connection->delete(self::TABLE, ['id' => $event->id]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('label', Types::STRING, ['length' => Label::MAX_LENGTH]);
        $table->addColumn('unit_amount_in_cents', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['label'], 'catalog_product_label_unique');
    }
}
