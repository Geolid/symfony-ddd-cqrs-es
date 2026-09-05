<?php

declare(strict_types=1);

namespace Catalog\Listing\Infrastructure\Projection\Projector;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Event\ProductRepriced;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Domain\ValueObject\Label;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('catalog.listing.project_products')]
final readonly class DbalProductProjector extends AbstractDbalProjector
{
    public const string TABLE = 'catalog_listing_product';

    #[Subscribe(ProductListed::class)]
    public function onProductListed(ProductListed $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'label' => $event->label->value,
                'unit_price_in_cents' => $event->unitPrice->cents,
                'listed_at' => $event->listedAt,
            ],
            ['listed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ProductRepriced::class)]
    public function onProductRepriced(ProductRepriced $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'unit_price_in_cents' => $event->unitPrice->cents,
                'repriced_at' => $event->repricedAt,
            ],
            ['id' => $event->id],
            ['repriced_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('unit_price_in_cents', Types::INTEGER);
        $table->addColumn('listed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('repriced_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['label'], 'catalog_listing_product_label_unique');
    }
}
