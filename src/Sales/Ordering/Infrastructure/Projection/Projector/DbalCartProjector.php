<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\CartStatus;
use Sales\Ordering\Domain\Cart\Event\CartCheckedOut;
use Sales\Ordering\Domain\Cart\Event\CartCheckoutAbandoned;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Cart\Event\CartLineAdded;
use Sales\Ordering\Domain\Cart\Event\CartLineQuantityChanged;
use Sales\Ordering\Domain\Cart\Event\CartLineRemoved;
use Sales\Ordering\Domain\Cart\Event\CartStarted;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Webmozart\Assert\Assert;

#[Projector('sales.ordering.project_carts')]
final readonly class DbalCartProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_cart';

    #[Subscribe(CartStarted::class)]
    public function onCartStarted(CartStarted $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'buyer_id' => $event->buyerId,
                'status' => CartStatus::ACTIVE->value,
                'line_items' => [],
            ],
            ['line_items' => Types::JSON],
        );
    }

    #[Subscribe(CartLineAdded::class)]
    public function onCartLineAdded(CartLineAdded $event): void
    {
        $lines = $this->linesOf($event->id);
        $existing = $lines[$event->lineId] ?? null;

        $lines[$event->lineId] = [
            'line_id' => $event->lineId,
            'product_id' => $event->product->id,
            'label' => $event->product->label->value,
            'unit_price_in_cents' => $event->product->price->cents,
            'quantity' => (null !== $existing ? $existing['quantity'] : 0) + $event->quantity->value,
        ];

        $this->saveLines($event->id, $lines);
    }

    #[Subscribe(CartLineRemoved::class)]
    public function onCartLineRemoved(CartLineRemoved $event): void
    {
        $lines = $this->linesOf($event->id);
        unset($lines[$event->lineId]);

        $this->saveLines($event->id, $lines);
    }

    #[Subscribe(CartLineQuantityChanged::class)]
    public function onCartLineQuantityChanged(CartLineQuantityChanged $event): void
    {
        $lines = $this->linesOf($event->id);
        $line = $lines[$event->lineId];
        $line['quantity'] = $event->quantity->value;
        $lines[$event->lineId] = $line;

        $this->saveLines($event->id, $lines);
    }

    #[Subscribe(CartCheckedOut::class)]
    public function onCartCheckedOut(CartCheckedOut $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::CHECKOUT->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CartCheckoutAbandoned::class)]
    public function onCartCheckoutAbandoned(CartCheckoutAbandoned $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::ACTIVE->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CartConverted::class)]
    public function onCartConverted(CartConverted $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::CONVERTED->value],
            ['id' => $event->id],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('line_items', Types::JSON);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }

    /**
     * @return array<string, array{line_id: string, product_id: string, label: string, unit_price_in_cents: int, quantity: int}>
     */
    private function linesOf(string $id): array
    {
        $raw = $this->connection->fetchOne(\sprintf('SELECT line_items FROM %s WHERE id = :id', self::TABLE), ['id' => $id]);

        if (false === $raw) {
            return [];
        }

        Assert::string($raw);

        /** @var list<array{line_id: string, product_id: string, label: string, unit_price_in_cents: int, quantity: int}> $stored */
        $stored = json_decode($raw, true, flags: \JSON_THROW_ON_ERROR);

        $lines = [];
        foreach ($stored as $line) {
            $lines[$line['line_id']] = $line;
        }

        return $lines;
    }

    /**
     * @param array<string, array{line_id: string, product_id: string, label: string, unit_price_in_cents: int, quantity: int}> $lines
     */
    private function saveLines(string $id, array $lines): void
    {
        $this->connection->update(
            self::TABLE,
            ['line_items' => array_values($lines)],
            ['id' => $id],
            ['line_items' => Types::JSON],
        );
    }
}
