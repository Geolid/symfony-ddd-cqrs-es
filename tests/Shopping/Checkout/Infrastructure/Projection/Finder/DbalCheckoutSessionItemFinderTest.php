<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Checkout\Application\Finder\CheckoutSessionItem\CheckoutSessionItemFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSessionItem\CheckoutSessionItemResult;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;

/**
 * @extends AbstractIterableFinderTestCase<CheckoutSessionItemResult>
 */
final class DbalCheckoutSessionItemFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCheckoutSession(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();

        $item = CheckoutSessionBuilder::sample('items')[0];
        $checkoutSession = CheckoutSessionBuilder::new()->withItems([$item])->create();

        $this->store($other, $checkoutSession);

        // When
        $results = iterator_to_array($this->finder()->byCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($checkoutSession->id->toString(), $results[0]->checkoutSessionId);
        self::assertSame($item->productId, $results[0]->productId);
        self::assertSame($item->label->value, $results[0]->label);
        self::assertSame($item->unitPrice->cents, $results[0]->unitPriceInCents);
        self::assertSame($item->quantity->value, $results[0]->quantity);
    }

    protected function finder(): CheckoutSessionItemFinderInterface
    {
        return $this->service(CheckoutSessionItemFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $productIds = [];
        $checkoutSessions = [];
        for ($i = 0; $i < $count; ++$i) {
            $item = CheckoutSessionBuilder::sample('items')[0];
            $productIds[] = $item->productId;
            $checkoutSessions[] = CheckoutSessionBuilder::new()->withItems([$item])->create();
        }

        $this->store(...$checkoutSessions);

        return $productIds;
    }

    protected function idOf(object $result): string
    {
        return $result->productId;
    }
}
