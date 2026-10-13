<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Specification\CheckoutSessionExpiredSpecification;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Shopping\Checkout\Domain\ValueObject\TaxRate;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: CheckoutSessionId,
 *     cartId: string,
 *     customerId: string,
 *     items: list<CheckoutItem>,
 *     currency: Currency,
 *     taxRate: TaxRate,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     paymentId: string,
 *     openedAt: \DateTimeImmutable,
 *     expiredAt: \DateTimeImmutable,
 *     staledAt: \DateTimeImmutable,
 *     completedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<CheckoutSession, Inputs>
 */
final class CheckoutSessionFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return CheckoutSession::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => CheckoutSessionId::fromString($id)]);
    }

    public function withCartId(string $cartId): self
    {
        return $this->with(['cartId' => $cartId]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->with(['customerId' => $customerId]);
    }

    /**
     * @param list<CheckoutItem> $items
     */
    public function withItems(array $items): self
    {
        return $this->with(['items' => $items]);
    }

    public function withCurrency(string $currency): self
    {
        return $this->with(['currency' => Currency::from($currency)]);
    }

    public function withTaxRate(int $basisPoints): self
    {
        return $this->with(['taxRate' => TaxRateFactory::new(['basisPoints' => $basisPoints])->create()]);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->with(['shippingAddress' => $shippingAddress]);
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->with(['billingAddress' => $billingAddress]);
    }

    public function withOpenedAt(\DateTimeImmutable $openedAt): self
    {
        return $this->with(['openedAt' => $openedAt]);
    }

    public function expired(?\DateTimeImmutable $expiredAt = null): self
    {
        return $this->with(array_filter(['expiredAt' => $expiredAt]))->transition(
            static function (CheckoutSession $checkoutSession, array $inputs): void {
                $checkoutSession->expire($inputs['expiredAt']);
            },
        );
    }

    public function staled(?\DateTimeImmutable $staledAt = null): self
    {
        return $this->with(array_filter(['staledAt' => $staledAt]))->transition(
            static function (CheckoutSession $checkoutSession, array $inputs): void {
                $checkoutSession->stale($inputs['staledAt']);
            },
        );
    }

    public function completed(?string $paymentId = null, ?\DateTimeImmutable $completedAt = null): self
    {
        return $this->with(array_filter(['paymentId' => $paymentId, 'completedAt' => $completedAt]))->transition(
            static function (CheckoutSession $checkoutSession, array $inputs): void {
                $checkoutSession->complete(
                    $inputs['cartId'],
                    $inputs['customerId'],
                    $inputs['items'],
                    $inputs['currency'],
                    $inputs['shippingAddress'],
                    $inputs['billingAddress'],
                    $inputs['paymentId'],
                    $inputs['completedAt'],
                );
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return CheckoutSession::open(
            id: $parameters['id'],
            cartId: $parameters['cartId'],
            customerId: $parameters['customerId'],
            items: $parameters['items'],
            currency: $parameters['currency'],
            shippingAddress: $parameters['shippingAddress'],
            billingAddress: $parameters['billingAddress'],
            openedAt: $parameters['openedAt'],
        );
    }

    protected function initialize(): static
    {
        // The items share the FINAL currency and tax rate, so a withCurrency()/withTaxRate() override carries over.
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::isInstanceOf($parameters['currency'], Currency::class);
            $parameters['items'] ??= CheckoutItemFactory::new([
                'unitPrice' => MoneyFactory::new(['currency' => $parameters['currency']->value]),
                'taxRate' => $parameters['taxRate'],
            ])->many(faker()->numberBetween(1, 3))->create();

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => CheckoutSessionIdFactory::new(),
            'cartId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'currency' => Currency::EUR,
            'taxRate' => TaxRateFactory::new(),
            'shippingAddress' => PostalAddressFactory::new(),
            'billingAddress' => PostalAddressFactory::new(),
            'paymentId' => Uuid::uuid7()->toString(),
            'openedAt' => $now,
            'expiredAt' => $now->modify(\sprintf('+%d minutes', CheckoutSessionExpiredSpecification::TTL_MINUTES + 1)),
            'staledAt' => $now->modify('+1 minute'),
            'completedAt' => $now->modify('+5 minutes'),
        ];
    }
}
