<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Doubles;

use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class StubPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $status,
        private ?string $failingReference = null,
    ) {
    }

    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function void(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function refund(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function checkStatus(string $reference): string
    {
        if ($reference === $this->failingReference) {
            throw new \RuntimeException('Provider unreachable.');
        }

        return $this->status;
    }
}
