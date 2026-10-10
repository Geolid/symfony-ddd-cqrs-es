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
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
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
        $other = CheckoutSessionFactory::new()->create();
        $checkoutSession = CheckoutSessionFactory::new()->create();
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
        $this->finder()->ofId(CheckoutSessionIdFactory::new()->create()->toString());
    }

    #[Test]
    public function itFindsOpenByCart(): void
    {
        // Given
        $other = CheckoutSessionFactory::new()->create();
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $staledOnSameCart = CheckoutSessionFactory::new()->withCartId($checkoutSession->cartId)->staled()->create();
        $this->store($other, $checkoutSession, $staledOnSameCart);

        // When
        $result = $this->finder()->openOfCartOrNull($checkoutSession->cartId);
        $nothing = $this->finder()->openOfCartOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($result);
        self::assertSame($checkoutSession->id->toString(), $result->id);
        self::assertSame($checkoutSession->cartId, $result->cartId);
        self::assertSame($checkoutSession->customerId, $result->customerId);
        self::assertSame(
            array_map(CheckoutItemMapper::toArray(...), $checkoutSession->items),
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
            PostalAddressMapper::toArray($checkoutSession->shippingAddress),
            PostalAddressResultMapper::toArray($result->shippingAddress),
        );
        self::assertSame(
            PostalAddressMapper::toArray($checkoutSession->billingAddress),
            PostalAddressResultMapper::toArray($result->billingAddress),
        );
        $total = array_reduce(
            $checkoutSession->items,
            static fn (TaxedAmount $carry, CheckoutItem $item): TaxedAmount => $carry->plus($item->taxedTotal()),
            TaxedAmount::zero($checkoutSession->total->excludingTax->currency),
        );
        self::assertSame($total->excludingTax->cents, $result->totalExcludingTaxInCents);
        self::assertSame($total->taxAmount->cents, $result->totalTaxAmountInCents);
        self::assertSame($total->includingTax->cents, $result->totalIncludingTaxInCents);
        self::assertSame($checkoutSession->total->excludingTax->currency->value, $result->currency);
        self::assertSame($checkoutSession->items[0]->taxRate->basisPoints, $result->taxRateBasisPoints);
        self::assertSame(CheckoutSessionStatus::OPEN, $result->status);
        self::assertSameDate($checkoutSession->openedAt, $result->openedAt);

        self::assertNull($nothing);
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $other = CheckoutSessionFactory::new()->create();
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($other, $checkoutSession);

        // When
        $results = iterator_to_array($this->finder()->byCustomer($checkoutSession->customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($checkoutSession->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshOpened = CheckoutSessionFactory::new()->withOpenedAt($now->modify('+1 day'))->create();
        $staleOpened = CheckoutSessionFactory::new()->withOpenedAt($now->modify('-1 day'))->create();
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
        $checkoutSessions = CheckoutSessionFactory::new()->many($count)->create();
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

        $first = CheckoutSessionFactory::new()->withId(CheckoutSessionIdFactory::new(['id' => $largerId])->create())->withOpenedAt($now)->create();
        $second = CheckoutSessionFactory::new()->withId(CheckoutSessionIdFactory::new(['id' => $smallerId])->create())->withOpenedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
