<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Domain;

use AfterSales\Return\Domain\Event\WithdrawalApproved;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Return\Domain\Event\WithdrawalRejected;
use AfterSales\Return\Domain\Event\WithdrawalRequested;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherBuyerException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\PostalAddress;

final class WithdrawalTest extends AggregateRootTestCase
{
    private WithdrawalId $id;
    private string $orderId;
    private string $buyerId;
    private PostalAddress $shippingAddress;
    private \DateTimeImmutable $deliveredAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $receivedAt;
    private \DateTimeImmutable $approvedAt;
    private string $reason;
    private \DateTimeImmutable $rejectedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderId = WithdrawalBuilder::sample('orderId');
        $this->id = WithdrawalId::generate();
        $this->buyerId = WithdrawalBuilder::sample('buyerId');
        $this->shippingAddress = WithdrawalBuilder::sample('shippingAddress');
        $this->deliveredAt = WithdrawalBuilder::sample('deliveredAt');
        $this->requestedAt = WithdrawalBuilder::sample('requestedAt');
        $this->receivedAt = WithdrawalBuilder::sample('receivedAt');
        $this->approvedAt = WithdrawalBuilder::sample('approvedAt');
        $this->reason = WithdrawalBuilder::sample('reason');
        $this->rejectedAt = WithdrawalBuilder::sample('rejectedAt');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Withdrawal => Withdrawal::request(
                $this->id,
                $this->orderId,
                $this->buyerId,
                $this->buyerId,
                $this->shippingAddress,
                $this->deliveredAt,
                $this->requestedAt,
            ))
            ->then($this->requested());
    }

    #[Test]
    public function itRequestsAtWindowBoundary(): void
    {
        $now = $this->deliveredAt->modify('+14 days');

        $this
            ->given()
            ->when(fn (): Withdrawal => Withdrawal::request(
                $this->id,
                $this->orderId,
                $this->buyerId,
                $this->buyerId,
                $this->shippingAddress,
                $this->deliveredAt,
                $now,
            ))
            ->then(new WithdrawalRequested(
                $this->id->toString(),
                $this->orderId,
                $this->buyerId,
                $this->shippingAddress,
                $now,
            ));
    }

    #[Test]
    public function itCannotRequestForAnotherBuyer(): void
    {
        $this
            ->given()
            ->when(fn (): Withdrawal => Withdrawal::request(
                $this->id,
                $this->orderId,
                $this->buyerId,
                Uuid::uuid7()->toString(),
                $this->shippingAddress,
                $this->deliveredAt,
                $this->requestedAt,
            ))
            ->expectsException(CannotRequestWithdrawalForAnotherBuyerException::class);
    }

    #[Test]
    public function itCannotRequestWhenWindowExpired(): void
    {
        $now = $this->deliveredAt->modify('+14 days')->modify('+1 second');

        $this
            ->given()
            ->when(fn (): Withdrawal => Withdrawal::request(
                $this->id,
                $this->orderId,
                $this->buyerId,
                $this->buyerId,
                $this->shippingAddress,
                $this->deliveredAt,
                $now,
            ))
            ->expectsException(WithdrawalWindowExpiredException::class);
    }

    #[Test]
    public function itReceives(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Withdrawal $withdrawal) => $withdrawal->receive($this->receivedAt))
            ->then(new WithdrawalReceived($this->id->toString(), $this->receivedAt));
    }

    #[Test]
    public function itDoesNotReceiveWhenAlreadyReceived(): void
    {
        $this
            ->given($this->requested(), $this->received())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->receive(WithdrawalBuilder::sample('receivedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotReceiveWhenAlreadyApproved(): void
    {
        $this
            ->given($this->requested(), $this->received(), $this->approved())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->receive(WithdrawalBuilder::sample('receivedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotReceiveWhenAlreadyRejected(): void
    {
        $this
            ->given($this->requested(), $this->received(), $this->rejected())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->receive(WithdrawalBuilder::sample('receivedAt')))
            ->then();
    }

    #[Test]
    public function itApproves(): void
    {
        $this
            ->given($this->requested(), $this->received())
            ->when(fn (Withdrawal $withdrawal) => $withdrawal->approve($this->approvedAt))
            ->then($this->approved());
    }

    #[Test]
    public function itDoesNotApproveWhenAlreadyApproved(): void
    {
        $this
            ->given($this->requested(), $this->received(), $this->approved())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->approve(WithdrawalBuilder::sample('approvedAt')))
            ->then();
    }

    #[Test]
    public function itCannotApproveWhenNotReceived(): void
    {
        $this
            ->given($this->requested())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->approve(WithdrawalBuilder::sample('approvedAt')))
            ->expectsException(WithdrawalNotReceivedException::class);
    }

    #[Test]
    public function itRejects(): void
    {
        $this
            ->given($this->requested(), $this->received())
            ->when(fn (Withdrawal $withdrawal) => $withdrawal->reject($this->reason, $this->rejectedAt))
            ->then($this->rejected());
    }

    #[Test]
    public function itDoesNotRejectWhenAlreadyRejected(): void
    {
        $this
            ->given($this->requested(), $this->received(), $this->rejected())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->reject(WithdrawalBuilder::sample('reason'), WithdrawalBuilder::sample('rejectedAt')))
            ->then();
    }

    #[Test]
    public function itCannotRejectWhenNotReceived(): void
    {
        $this
            ->given($this->requested())
            ->when(static fn (Withdrawal $withdrawal) => $withdrawal->reject(WithdrawalBuilder::sample('reason'), WithdrawalBuilder::sample('rejectedAt')))
            ->expectsException(WithdrawalNotReceivedException::class);
    }

    protected function aggregateClass(): string
    {
        return Withdrawal::class;
    }

    private function requested(): WithdrawalRequested
    {
        return new WithdrawalRequested(
            $this->id->toString(),
            $this->orderId,
            $this->buyerId,
            $this->shippingAddress,
            $this->requestedAt,
        );
    }

    private function received(): WithdrawalReceived
    {
        return new WithdrawalReceived($this->id->toString(), $this->receivedAt);
    }

    private function approved(): WithdrawalApproved
    {
        return new WithdrawalApproved($this->id->toString(), $this->approvedAt);
    }

    private function rejected(): WithdrawalRejected
    {
        return new WithdrawalRejected($this->id->toString(), $this->reason, $this->rejectedAt);
    }
}
