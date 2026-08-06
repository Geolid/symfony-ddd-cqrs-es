<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Exception\OrderPaymentInvalidTransitionException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentStatus;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;

#[Aggregate('sales.order.payment')]
final class OrderPayment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private OrderPaymentId $id;
    private string $orderId;
    private string $customerId;
    private ?string $buyerAddress;
    private Money $amount;
    private PaymentReference $reference;
    private string $checkoutUrl;
    private OrderPaymentStatus $status;

    public function id(): OrderPaymentId
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function reference(): PaymentReference
    {
        return $this->reference;
    }

    public function checkoutUrl(): string
    {
        return $this->checkoutUrl;
    }

    public function status(): OrderPaymentStatus
    {
        return $this->status;
    }

    public static function request(
        OrderPaymentId $id,
        string $orderId,
        string $customerId,
        ?string $buyerAddress,
        Money $amount,
        PaymentReference $reference,
        string $checkoutUrl,
        \DateTimeImmutable $requestedAt,
    ): self {
        $self = new self();
        $self->recordThat(new OrderPaymentRequested(
            id: $id->toString(),
            orderId: $orderId,
            customerId: $customerId,
            buyerAddress: $buyerAddress,
            amountInCents: $amount->toCents(),
            reference: $reference->toString(),
            checkoutUrl: $checkoutUrl,
            requestedAt: $requestedAt->format('c'),
        ));

        return $self;
    }

    /**
     * @throws OrderPaymentInvalidTransitionException
     */
    public function capture(\DateTimeImmutable $capturedAt): void
    {
        if (!$this->status->isRequested()) {
            throw OrderPaymentInvalidTransitionException::cannotCapture();
        }

        $this->recordThat(new OrderPaymentCaptured(
            id: $this->id->toString(),
            orderId: $this->orderId,
            customerId: $this->customerId,
            buyerAddress: $this->buyerAddress,
            capturedAt: $capturedAt->format('c'),
        ));
    }

    #[Apply]
    private function applyOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->id = OrderPaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;
        $this->buyerAddress = $event->buyerAddress;
        $this->amount = Money::fromCents($event->amountInCents);
        $this->reference = PaymentReference::fromString($event->reference);
        $this->checkoutUrl = $event->checkoutUrl;
        $this->status = OrderPaymentStatus::REQUESTED;
    }

    #[Apply]
    private function applyOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->status = OrderPaymentStatus::CAPTURED;
    }
}
