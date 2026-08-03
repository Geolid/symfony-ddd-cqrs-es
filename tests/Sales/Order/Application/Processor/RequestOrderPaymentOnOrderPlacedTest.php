<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Gateway\PaymentGatewayInterface;
use Sales\Order\Application\Processor\RequestOrderPaymentOnOrderPlaced;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\OrderPaymentId;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class RequestOrderPaymentOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    private DummyPaymentGateway $paymentGateway;

    private RequestOrderPaymentOnOrderPlaced $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new DummyPaymentGateway();
        $this->processor = new RequestOrderPaymentOnOrderPlaced(
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itRequestsPaymentOnOrderPlaced(): void
    {
        // Given
        $event = new OrderPlaced(
            'order-1',
            'customer-1',
            'buyer@example.com',
            [['label' => 'Assorted goods', 'quantity' => 1, 'unitAmountInCents' => 4_200]],
            4_200,
            '2026-01-01T00:00:00+00:00',
        );

        // When
        ($this->processor)($event);

        // Then
        self::assertSame('order-1', $this->paymentGateway->orderId);
        self::assertSame(4_200, $this->paymentGateway->amountInCents);

        $orderPayment = $this->service(OrderPaymentRepositoryInterface::class)->load(OrderPaymentId::forOrder('order-1'));
        self::assertSame(DummyPaymentGateway::CHARGE_REFERENCE, $orderPayment->reference()->toString());
    }
}

final class DummyPaymentGateway implements PaymentGatewayInterface
{
    public const string CHARGE_REFERENCE = 'GLBX-9F3K2M1P';

    public ?string $orderId = null;

    public ?int $amountInCents = null;

    public function requestPayment(string $orderId, int $amountInCents): string
    {
        $this->orderId = $orderId;
        $this->amountInCents = $amountInCents;

        return self::CHARGE_REFERENCE;
    }
}
