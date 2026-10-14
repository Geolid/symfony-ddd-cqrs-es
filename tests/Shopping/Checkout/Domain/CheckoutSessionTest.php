<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\TaxedAmount;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\Event\CheckoutSessionStaled;
use Shopping\Checkout\Domain\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\Service\CheckoutSessionExpiryCalculator;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Shopping\Tests\Checkout\Support\Factory\CheckoutItemFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
use Symfony\Component\Clock\Clock;

final class CheckoutSessionTest extends AggregateRootTestCase
{
    private CheckoutSessionId $id;
    private string $cartId;
    private string $customerId;
    /** @var list<CheckoutItem> */
    private array $items;
    private Currency $currency;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;
    private string $paymentId;
    private \DateTimeImmutable $openedAt;
    private int $expirySeconds;
    private \DateTimeImmutable $expiresAt;
    private \DateTimeImmutable $expiredAt;
    private \DateTimeImmutable $staledAt;
    private \DateTimeImmutable $completedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CheckoutSessionIdFactory::new()->create();
        $this->cartId = Uuid::uuid7()->toString();
        $this->customerId = Uuid::uuid7()->toString();
        $this->items = CheckoutItemFactory::new()->many(2)->create();
        $this->currency = Currency::EUR;
        $this->shippingAddress = PostalAddressFactory::new()->create();
        $this->billingAddress = PostalAddressFactory::new()->create();
        $this->paymentId = Uuid::uuid7()->toString();
        $this->openedAt = Clock::get()->now();
        $this->expirySeconds = 60 * 60;
        $this->expiresAt = $this->openedAt->modify(\sprintf('+%d seconds', $this->expirySeconds));
        $this->expiredAt = $this->expiresAt->modify('+1 minute');
        $this->staledAt = $this->openedAt->modify('+1 minute');
        $this->completedAt = $this->openedAt->modify('+5 minutes');
    }

    #[Test]
    public function itOpens(): void
    {
        $this
            ->given()
            ->when(fn (): CheckoutSession => CheckoutSession::open(
                $this->id,
                $this->cartId,
                $this->customerId,
                $this->items,
                $this->currency,
                $this->shippingAddress,
                $this->billingAddress,
                $this->openedAt,
                new CheckoutSessionExpiryCalculator($this->expirySeconds),
            ))
            ->then($this->opened());
    }

    #[Test]
    public function itCannotOpenWhenEmpty(): void
    {
        $this
            ->given()
            ->when(fn (): CheckoutSession => CheckoutSession::open(
                $this->id,
                $this->cartId,
                $this->customerId,
                [],
                $this->currency,
                $this->shippingAddress,
                $this->billingAddress,
                $this->openedAt,
                new CheckoutSessionExpiryCalculator($this->expirySeconds),
            ))
            ->expectsException(CheckoutSessionEmptyException::class);
    }

    #[Test]
    public function itExpiresWhenOpen(): void
    {
        $this
            ->given($this->opened())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->expire($this->expiredAt))
            ->then(new CheckoutSessionExpired($this->id->toString(), $this->expiredAt));
    }

    #[Test]
    public function itDoesNotExpireWhenAlreadyExpired(): void
    {
        $this
            ->given($this->opened(), $this->expired())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->expire($this->expiredAt))
            ->then();
    }

    #[Test]
    public function itDoesNotExpireWhenExpiresAtNotPassed(): void
    {
        $this
            ->given($this->opened())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->expire($this->expiresAt))
            ->then();
    }

    #[Test]
    public function itStalesWhenOpen(): void
    {
        $this
            ->given($this->opened())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->stale($this->staledAt))
            ->then(new CheckoutSessionStaled($this->id->toString(), $this->staledAt));
    }

    #[Test]
    public function itDoesNotStaleWhenAlreadyStaled(): void
    {
        $this
            ->given($this->opened(), $this->staled())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->stale($this->staledAt))
            ->then();
    }

    #[Test]
    public function itCompletesWhenOpen(): void
    {
        $this
            ->given($this->opened())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->complete(
                $this->cartId,
                $this->customerId,
                $this->items,
                $this->currency,
                $this->shippingAddress,
                $this->billingAddress,
                $this->paymentId,
                $this->completedAt,
            ))
            ->then($this->completed());
    }

    #[Test]
    public function itDoesNotCompleteWhenAlreadyCompleted(): void
    {
        $this
            ->given($this->opened(), $this->completed())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->complete(
                $this->cartId,
                $this->customerId,
                $this->items,
                $this->currency,
                $this->shippingAddress,
                $this->billingAddress,
                $this->paymentId,
                $this->completedAt,
            ))
            ->then();
    }

    #[Test]
    public function itDoesNotExpireWhenStaled(): void
    {
        $this
            ->given($this->opened(), $this->staled())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->expire($this->expiredAt))
            ->then();
    }

    #[Test]
    public function itDoesNotStaleWhenCompleted(): void
    {
        $this
            ->given($this->opened(), $this->completed())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->stale($this->staledAt))
            ->then();
    }

    #[Test]
    public function itDoesNotCompleteWhenExpired(): void
    {
        $this
            ->given($this->opened(), $this->expired())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->complete(
                $this->cartId,
                $this->customerId,
                $this->items,
                $this->currency,
                $this->shippingAddress,
                $this->billingAddress,
                $this->paymentId,
                $this->completedAt,
            ))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return CheckoutSession::class;
    }

    private function opened(): CheckoutSessionOpened
    {
        return new CheckoutSessionOpened(
            $this->id->toString(),
            $this->cartId,
            $this->customerId,
            $this->items,
            $this->shippingAddress,
            $this->billingAddress,
            $this->total(),
            $this->openedAt,
            $this->expiresAt,
        );
    }

    private function expired(): CheckoutSessionExpired
    {
        return new CheckoutSessionExpired($this->id->toString(), $this->expiredAt);
    }

    private function staled(): CheckoutSessionStaled
    {
        return new CheckoutSessionStaled($this->id->toString(), $this->staledAt);
    }

    private function completed(): CheckoutSessionCompleted
    {
        return new CheckoutSessionCompleted(
            $this->id->toString(),
            $this->cartId,
            $this->customerId,
            $this->items,
            $this->shippingAddress,
            $this->billingAddress,
            $this->total(),
            $this->paymentId,
            $this->completedAt,
        );
    }

    private function total(): TaxedAmount
    {
        return array_reduce(
            $this->items,
            static fn (TaxedAmount $carry, CheckoutItem $item): TaxedAmount => $carry->plus($item->taxedTotal()),
            TaxedAmount::zero($this->currency),
        );
    }
}
