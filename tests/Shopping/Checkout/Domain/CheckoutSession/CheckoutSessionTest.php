<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\CheckoutSession;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionStaled;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;

final class CheckoutSessionTest extends AggregateRootTestCase
{
    private CheckoutSessionId $id;
    private string $cartId;
    private string $customerId;
    /** @var list<CheckoutItem> */
    private array $items;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;
    private string $paymentId;
    private \DateTimeImmutable $openedAt;
    private \DateTimeImmutable $expiredAt;
    private \DateTimeImmutable $staledAt;
    private \DateTimeImmutable $completedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CheckoutSessionId::fromString(Uuid::uuid7()->toString());
        $this->cartId = CheckoutSessionBuilder::sample('cartId');
        $this->customerId = CheckoutSessionBuilder::sample('customerId');
        $this->items = CheckoutSessionBuilder::sample('items');
        $this->shippingAddress = CheckoutSessionBuilder::sample('shippingAddress');
        $this->billingAddress = CheckoutSessionBuilder::sample('billingAddress');
        $this->paymentId = CheckoutSessionBuilder::sample('paymentId');
        $this->openedAt = CheckoutSessionBuilder::sample('openedAt');
        $this->expiredAt = CheckoutSessionBuilder::sample('expiredAt');
        $this->staledAt = CheckoutSessionBuilder::sample('staledAt');
        $this->completedAt = CheckoutSessionBuilder::sample('completedAt');
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
                $this->shippingAddress,
                $this->billingAddress,
                $this->openedAt,
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
                $this->shippingAddress,
                $this->billingAddress,
                $this->openedAt,
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
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->expire(CheckoutSessionBuilder::sample('expiredAt')))
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
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->stale(CheckoutSessionBuilder::sample('staledAt')))
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
                $this->shippingAddress,
                $this->billingAddress,
                $this->paymentId,
                CheckoutSessionBuilder::sample('completedAt'),
            ))
            ->then();
    }

    #[Test]
    public function itDoesNotExpireWhenStaled(): void
    {
        $this
            ->given($this->opened(), $this->staled())
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->expire(CheckoutSessionBuilder::sample('expiredAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotStaleWhenCompleted(): void
    {
        $this
            ->given($this->opened(), $this->completed())
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->stale(CheckoutSessionBuilder::sample('staledAt')))
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
                $this->shippingAddress,
                $this->billingAddress,
                $this->paymentId,
                CheckoutSessionBuilder::sample('completedAt'),
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
            $this->totalAmount(),
            $this->openedAt,
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
            $this->totalAmount(),
            $this->paymentId,
            $this->completedAt,
        );
    }

    private function totalAmount(): Money
    {
        return array_reduce(
            $this->items,
            static fn (Money $carry, CheckoutItem $item): Money => $carry->plus($item->subtotal()),
            Money::fromCents(0),
        );
    }
}
