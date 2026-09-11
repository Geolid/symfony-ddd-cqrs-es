<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\CheckoutSession;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionConsumed;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionStaled;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;

final class CheckoutSessionTest extends AggregateRootTestCase
{
    private CheckoutSessionId $id;
    private string $cartId;
    private string $shopperId;
    /** @var list<Line> */
    private array $lines;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;
    private int $totalAmountInCents;
    private \DateTimeImmutable $openedAt;
    private \DateTimeImmutable $expiredAt;
    private \DateTimeImmutable $staledAt;
    private \DateTimeImmutable $consumedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CheckoutSessionId::fromString(Uuid::uuid7()->toString());
        $this->cartId = CheckoutSessionBuilder::sample('cartId');
        $this->shopperId = CheckoutSessionBuilder::sample('shopperId');
        $this->lines = CheckoutSessionBuilder::sample('lines');
        $this->shippingAddress = CheckoutSessionBuilder::sample('shippingAddress');
        $this->billingAddress = CheckoutSessionBuilder::sample('billingAddress');
        $this->totalAmountInCents = CheckoutSessionBuilder::sample('totalAmountInCents');
        $this->openedAt = CheckoutSessionBuilder::sample('openedAt');
        $this->expiredAt = CheckoutSessionBuilder::sample('expiredAt');
        $this->staledAt = CheckoutSessionBuilder::sample('staledAt');
        $this->consumedAt = CheckoutSessionBuilder::sample('consumedAt');
    }

    #[Test]
    public function itOpens(): void
    {
        $this
            ->given()
            ->when(fn (): CheckoutSession => CheckoutSession::open(
                $this->id,
                $this->cartId,
                $this->shopperId,
                $this->lines,
                $this->shippingAddress,
                $this->billingAddress,
                $this->totalAmountInCents,
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
                $this->shopperId,
                [],
                $this->shippingAddress,
                $this->billingAddress,
                $this->totalAmountInCents,
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
    public function itConsumesWhenOpen(): void
    {
        $this
            ->given($this->opened())
            ->when(fn (CheckoutSession $checkoutSession) => $checkoutSession->consume($this->consumedAt))
            ->then(new CheckoutSessionConsumed($this->id->toString(), $this->consumedAt));
    }

    #[Test]
    public function itDoesNotConsumeWhenAlreadyConsumed(): void
    {
        $this
            ->given($this->opened(), $this->consumed())
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->consume(CheckoutSessionBuilder::sample('consumedAt')))
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
    public function itDoesNotStaleWhenConsumed(): void
    {
        $this
            ->given($this->opened(), $this->consumed())
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->stale(CheckoutSessionBuilder::sample('staledAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotConsumeWhenExpired(): void
    {
        $this
            ->given($this->opened(), $this->expired())
            ->when(static fn (CheckoutSession $checkoutSession) => $checkoutSession->consume(CheckoutSessionBuilder::sample('consumedAt')))
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
            $this->shopperId,
            $this->lines,
            $this->shippingAddress,
            $this->billingAddress,
            $this->totalAmountInCents,
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

    private function consumed(): CheckoutSessionConsumed
    {
        return new CheckoutSessionConsumed($this->id->toString(), $this->consumedAt);
    }
}
