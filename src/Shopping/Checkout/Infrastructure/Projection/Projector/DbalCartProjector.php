<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Domain\Cart\Event\CartLineAdded;
use Shopping\Checkout\Domain\Cart\Event\CartLineQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartLineRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartStarted;

#[Projector('shopping.checkout.project_carts')]
final readonly class DbalCartProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_cart';

    #[Subscribe(CartStarted::class)]
    public function onCartStarted(CartStarted $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'shopper_id' => $event->shopperId,
                'lines_json' => [],
                'total_amount_in_cents' => 0,
            ],
            ['lines_json' => Types::JSON],
        );
    }

    #[Subscribe(CartLineAdded::class)]
    public function onCartLineAdded(CartLineAdded $event): void
    {
        $lines = $this->currentLines($event->id);

        $existing = $lines[$event->lineId] ?? null;
        $quantity = null !== $existing ? $existing['quantity'] + $event->quantity->value : $event->quantity->value;

        $lines[$event->lineId] = [
            'lineId' => $event->lineId,
            'productId' => $event->product->id,
            'label' => $event->product->label->value,
            'unitPriceInCents' => $event->product->price->cents,
            'quantity' => $quantity,
        ];

        $this->writeLines($event->id, $lines);
    }

    #[Subscribe(CartLineRemoved::class)]
    public function onCartLineRemoved(CartLineRemoved $event): void
    {
        $lines = $this->currentLines($event->id);
        unset($lines[$event->lineId]);

        $this->writeLines($event->id, $lines);
    }

    #[Subscribe(CartLineQuantityChanged::class)]
    public function onCartLineQuantityChanged(CartLineQuantityChanged $event): void
    {
        $lines = $this->currentLines($event->id);
        $existing = $lines[$event->lineId];

        $lines[$event->lineId] = [
            'lineId' => $existing['lineId'],
            'productId' => $existing['productId'],
            'label' => $existing['label'],
            'unitPriceInCents' => $existing['unitPriceInCents'],
            'quantity' => $event->quantity->value,
        ];

        $this->writeLines($event->id, $lines);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('shopper_id', Types::STRING, ['length' => 36]);
        $table->addColumn('lines_json', Types::JSON);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }

    /**
     * @return array<string, array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}>
     */
    private function currentLines(string $cartId): array
    {
        $lines = $this->connection->fetchOne(
            \sprintf('SELECT lines_json FROM %s WHERE id = :id', self::TABLE),
            ['id' => $cartId],
        );

        \assert(\is_string($lines));

        /** @var list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $decoded */
        $decoded = json_decode($lines, true, 512, \JSON_THROW_ON_ERROR);

        $indexed = [];
        foreach ($decoded as $line) {
            $indexed[$line['lineId']] = $line;
        }

        return $indexed;
    }

    /**
     * @param array<string, array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     */
    private function writeLines(string $cartId, array $lines): void
    {
        $totalAmountInCents = array_sum(array_map(
            static fn (array $line): int => $line['unitPriceInCents'] * $line['quantity'],
            $lines,
        ));

        $this->connection->update(
            self::TABLE,
            [
                'lines_json' => array_values($lines),
                'total_amount_in_cents' => $totalAmountInCents,
            ],
            ['id' => $cartId],
            ['lines_json' => Types::JSON],
        );
    }
}
