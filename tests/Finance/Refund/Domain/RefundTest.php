<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Domain;

use Finance\Refund\Domain\Event\RefundConfirmed;
use Finance\Refund\Domain\Event\RefundInitiated;
use Finance\Refund\Domain\Refund;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Money;

final class RefundTest extends AggregateRootTestCase
{
    private string $paymentId;
    private string $orderId;
    private Money $amount;
    private \DateTimeImmutable $initiatedAt;
    private \DateTimeImmutable $refundedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentId = RefundBuilder::sample('paymentId');
        $this->orderId = RefundBuilder::sample('orderId');
        $this->amount = RefundBuilder::sample('amount');
        $this->initiatedAt = RefundBuilder::sample('initiatedAt');
        $this->refundedAt = RefundBuilder::sample('refundedAt');
    }

    #[Test]
    public function itInitiatesForPayment(): void
    {
        $this
            ->given()
            ->when(fn (): Refund => Refund::initiate(
                RefundId::forPayment($this->paymentId),
                $this->paymentId,
                $this->orderId,
                $this->amount,
                $this->initiatedAt,
            ))
            ->then($this->initiated());
    }

    #[Test]
    public function itConfirmsWhenInitiated(): void
    {
        $this
            ->given($this->initiated())
            ->when(fn (Refund $refund) => $refund->confirm($this->refundedAt))
            ->then(new RefundConfirmed(RefundId::forPayment($this->paymentId)->toString(), $this->refundedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $this
            ->given($this->initiated(), new RefundConfirmed(RefundId::forPayment($this->paymentId)->toString(), $this->refundedAt))
            ->when(static fn (Refund $refund) => $refund->confirm(RefundBuilder::sample('refundedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Refund::class;
    }

    private function initiated(): RefundInitiated
    {
        return new RefundInitiated(
            RefundId::forPayment($this->paymentId)->toString(),
            $this->paymentId,
            $this->orderId,
            $this->amount->cents,
            $this->initiatedAt,
        );
    }
}
