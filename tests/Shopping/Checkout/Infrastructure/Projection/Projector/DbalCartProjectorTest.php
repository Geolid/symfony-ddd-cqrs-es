<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartProjector;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{shopper_id: string, lines_json: string, total_amount_in_cents: int}
 * @phpstan-type Line array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}
 */
final class DbalCartProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartStarted(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $builder = CartBuilder::new();
        $cart = $builder->create();

        // When
        $this->store($other, $cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['shopperId'], $row['shopper_id']);
        self::assertSame([], $this->decodedLines($row));
        self::assertSame(0, $row['total_amount_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCartLineAdded(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->create();
        $this->store($other);

        $productA = CartBuilder::sample('product');
        $productB = CartBuilder::sample('product');
        $builder = CartBuilder::new()
            ->lineAdded($productA, $quantityA1 = CartBuilder::sample('quantity'))
            ->lineAdded($productA, $quantityA2 = CartBuilder::sample('quantity'))
            ->lineAdded($productB, $quantityB = CartBuilder::sample('quantity'));
        $cart = $builder->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        $lines = $this->decodedLines($row);
        self::assertCount(2, $lines);

        $lineIdA = LineId::forProduct($cart->id->toString(), $productA->id)->toString();
        self::assertSame([
            'lineId' => $lineIdA,
            'productId' => $productA->id,
            'label' => $productA->label->value,
            'unitPriceInCents' => $productA->price->cents,
            'quantity' => $quantityA1->value + $quantityA2->value,
        ], $lines[$lineIdA]);

        $lineIdB = LineId::forProduct($cart->id->toString(), $productB->id)->toString();
        self::assertSame([
            'lineId' => $lineIdB,
            'productId' => $productB->id,
            'label' => $productB->label->value,
            'unitPriceInCents' => $productB->price->cents,
            'quantity' => $quantityB->value,
        ], $lines[$lineIdB]);

        $expectedTotal = $productA->price->cents * ($quantityA1->value + $quantityA2->value)
            + $productB->price->cents * $quantityB->value;
        self::assertSame($expectedTotal, $row['total_amount_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertCount(1, $this->decodedLines($otherRow));
    }

    #[Test]
    public function itProjectsOnCartLineRemoved(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->create();
        $this->store($other);

        $productA = CartBuilder::sample('product');
        $productB = CartBuilder::sample('product');
        $cart = CartBuilder::new()
            ->lineAdded($productA)
            ->lineAdded($productB)
            ->lineRemoved()
            ->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        $lines = $this->decodedLines($row);
        self::assertCount(1, $lines);

        $lineIdA = LineId::forProduct($cart->id->toString(), $productA->id)->toString();
        self::assertArrayHasKey($lineIdA, $lines);
        $lineIdB = LineId::forProduct($cart->id->toString(), $productB->id)->toString();
        self::assertArrayNotHasKey($lineIdB, $lines);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertCount(1, $this->decodedLines($otherRow));
    }

    #[Test]
    public function itProjectsOnCartLineQuantityChanged(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->create();
        $this->store($other);

        $unchangedProduct = CartBuilder::sample('product');
        $unchangedQuantity = CartBuilder::sample('quantity');
        $product = CartBuilder::sample('product');
        $builder = CartBuilder::new()
            ->lineAdded($unchangedProduct, $unchangedQuantity)
            ->lineAdded($product)
            ->lineQuantityChanged($newQuantity = CartBuilder::sample('quantity'));
        $cart = $builder->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        $lines = $this->decodedLines($row);
        self::assertCount(2, $lines);

        $unchangedLineId = LineId::forProduct($cart->id->toString(), $unchangedProduct->id)->toString();
        self::assertSame([
            'lineId' => $unchangedLineId,
            'productId' => $unchangedProduct->id,
            'label' => $unchangedProduct->label->value,
            'unitPriceInCents' => $unchangedProduct->price->cents,
            'quantity' => $unchangedQuantity->value,
        ], $lines[$unchangedLineId]);

        $lineId = LineId::forProduct($cart->id->toString(), $product->id)->toString();
        self::assertSame([
            'lineId' => $lineId,
            'productId' => $product->id,
            'label' => $product->label->value,
            'unitPriceInCents' => $product->price->cents,
            'quantity' => $newQuantity->value,
        ], $lines[$lineId]);

        $expectedTotal = $unchangedProduct->price->cents * $unchangedQuantity->value + $product->price->cents * $newQuantity->value;
        self::assertSame($expectedTotal, $row['total_amount_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertCount(1, $this->decodedLines($otherRow));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT shopper_id, lines_json, total_amount_in_cents FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );
    }

    /**
     * @param Row $row
     *
     * @return array<string, Line>
     */
    private function decodedLines(array $row): array
    {
        /** @var list<Line> $decoded */
        $decoded = json_decode($row['lines_json'], true, flags: \JSON_THROW_ON_ERROR);

        $indexed = [];
        foreach ($decoded as $line) {
            $indexed[$line['lineId']] = $line;
        }

        return $indexed;
    }
}
