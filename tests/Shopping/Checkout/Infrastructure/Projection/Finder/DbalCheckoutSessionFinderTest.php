<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\TaxedAmount;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionItemResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Shopping\Tests\Checkout\Support\PostalAddressResultMapper;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<CheckoutSessionResult>
 */
final class DbalCheckoutSessionFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGets(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->store($other, $checkoutSession);

        // When
        $result = $this->finder()->ofId($checkoutSession->id->toString());

        // Then
        self::assertSame($checkoutSession->id->toString(), $result->id);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFindsByCart(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();
        $this->store($other, $checkoutSession);

        // When
        $result = $this->finder()->ofCartOrNull($builder['cartId']);
        $nothing = $this->finder()->ofCartOrNull(CheckoutSessionBuilder::sample('cartId'));

        // Then
        self::assertNotNull($result);
        self::assertSame($checkoutSession->id->toString(), $result->id);
        self::assertSame($builder['cartId'], $result->cartId);
        self::assertSame($builder['customerId'], $result->customerId);
        self::assertSame(
            array_map(CheckoutItemMapper::toArray(...), $builder['items']),
            array_map(
                static fn (CheckoutSessionItemResult $item): array => [
                    'productId' => $item->productId,
                    'label' => $item->label,
                    'unitPriceInCents' => $item->unitPriceInCents,
                    'quantity' => $item->quantity,
                ],
                $result->items,
            ),
        );
        self::assertSame(
            PostalAddressMapper::toArray($builder['shippingAddress']),
            PostalAddressResultMapper::toArray($result->shippingAddress),
        );
        self::assertSame(
            PostalAddressMapper::toArray($builder['billingAddress']),
            PostalAddressResultMapper::toArray($result->billingAddress),
        );
        $total = array_reduce(
            $builder['items'],
            static fn (TaxedAmount $carry, CheckoutItem $item): TaxedAmount => $carry->plus($item->taxedTotal()),
            TaxedAmount::zero($builder['currency']),
        );
        self::assertSame($total->excludingTax->cents, $result->totalExcludingTaxInCents);
        self::assertSame($total->taxAmount->cents, $result->totalTaxAmountInCents);
        self::assertSame($total->includingTax->cents, $result->totalIncludingTaxInCents);
        self::assertSame($builder['currency']->value, $result->currency);
        self::assertSame($builder['taxRate']->basisPoints, $result->taxRateBasisPoints);
        self::assertSame(CheckoutSessionStatus::OPEN, $result->status);
        self::assertSame($builder['openedAt']->format('Y-m-d H:i:s'), $result->openedAt->format('Y-m-d H:i:s'));

        self::assertNull($nothing);
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();
        $this->store($other, $checkoutSession);

        // When
        $results = iterator_to_array($this->finder()->byCustomer($builder['customerId']));

        // Then
        self::assertCount(1, $results);
        self::assertSame($checkoutSession->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshOpened = CheckoutSessionBuilder::new()->withOpenedAt($now->modify('+1 day'))->create();
        $staleOpened = CheckoutSessionBuilder::new()->withOpenedAt($now->modify('-1 day'))->create();
        $this->store($freshOpened, $staleOpened);

        // When
        $results = iterator_to_array($this->finder()->stalledBefore($now));

        // Then
        self::assertCount(1, $results);
        self::assertSame($staleOpened->id->toString(), $results[0]->id);
    }

    protected function finder(): CheckoutSessionFinderInterface
    {
        return $this->service(CheckoutSessionFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $checkoutSessions = CheckoutSessionBuilder::new()->many($count)->create();
        $this->store(...$checkoutSessions);

        return array_map(static fn (CheckoutSession $checkoutSession): string => $checkoutSession->id->toString(), $checkoutSessions);
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

        $first = CheckoutSessionBuilder::new()->withId($largerId)->withOpenedAt($now)->create();
        $second = CheckoutSessionBuilder::new()->withId($smallerId)->withOpenedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
