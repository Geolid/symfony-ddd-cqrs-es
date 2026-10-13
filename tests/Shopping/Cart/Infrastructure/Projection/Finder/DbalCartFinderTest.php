<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Shopping\Cart\Application\CartStatus;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface;
use Shopping\Cart\Application\Finder\Cart\CartResult;
use Shopping\Cart\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Cart\Domain\Cart;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<CartResult>
 */
final class DbalCartFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGets(): void
    {
        // Given
        $other = CartFactory::new()->create();
        $cart = CartFactory::new()->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder()->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->id);
        self::assertSame($cart->customerId, $result->customerId);
        self::assertSame(CartStatus::ACTIVE, $result->status);
        self::assertSame($cart->startedAt->format('Y-m-d H:i:s'), $result->startedAt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->finder()->ofId(CartIdFactory::new()->create()->toString());
    }

    #[Test]
    public function itFiltersActiveById(): void
    {
        // Given
        $purchasedInList = CartFactory::new()->purchased()->create();
        $activeNotInList = CartFactory::new()->create();
        $cart = CartFactory::new()->create();
        $this->store($purchasedInList, $activeNotInList, $cart);

        // When
        $results = iterator_to_array($this->finder()->activeById($cart->id->toString(), $purchasedInList->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cart->id->toString(), $results[0]->id);
    }

    protected function finder(): CartFinderInterface
    {
        return $this->service(CartFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $carts = CartFactory::new()->many($count)->create();
        $this->store(...$carts);

        return array_map(static fn (Cart $cart): string => $cart->id->toString(), $carts);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $now = Clock::get()->now();
        $smallerId = Uuid::uuid7($now)->toString();
        $largerId = Uuid::uuid7($now->modify('+1 hour'))->toString();

        $first = CartFactory::new()->withId($largerId)->withStartedAt($now)->create();
        $second = CartFactory::new()->withId($smallerId)->withStartedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
