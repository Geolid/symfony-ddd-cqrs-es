<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Finder\CheckoutSessionLine\CheckoutSessionLineFinderInterface;
use Sales\Ordering\Application\Finder\CheckoutSessionLine\CheckoutSessionLineResult;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;

/**
 * @extends AbstractIterableFinderTestCase<CheckoutSessionLineResult>
 */
final class DbalCheckoutSessionLineFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCheckoutSession(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->withLines([CheckoutSessionBuilder::sample('lines')[0]])->create();
        $builder = CheckoutSessionBuilder::new()->withLines([CheckoutSessionBuilder::sample('lines')[0]]);
        $checkoutSession = $builder->create();
        $this->store($other, $checkoutSession);

        // When
        $results = iterator_to_array($this->finder()->byCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($checkoutSession->id->toString(), $results[0]->checkoutSessionId);
        self::assertSame($builder['lines'][0]->product->id, $results[0]->productId);
        self::assertSame($builder['lines'][0]->product->label->value, $results[0]->label);
        self::assertSame($builder['lines'][0]->product->price->cents, $results[0]->unitPriceInCents);
        self::assertSame($builder['lines'][0]->quantity->value, $results[0]->quantity);
    }

    protected function finder(): CheckoutSessionLineFinderInterface
    {
        return $this->service(CheckoutSessionLineFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $builders = [];
        for ($i = 0; $i < $count; ++$i) {
            $builders[] = CheckoutSessionBuilder::new()->withLines([CheckoutSessionBuilder::sample('lines')[0]]);
        }

        $checkoutSessions = array_map(static fn (CheckoutSessionBuilder $builder): CheckoutSession => $builder->create(), $builders);
        $this->store(...$checkoutSessions);

        return array_map(static fn (CheckoutSessionBuilder $builder): string => $builder['lines'][0]->id->toString(), $builders);
    }

    protected function idOf(object $result): string
    {
        return $result->lineId;
    }
}
