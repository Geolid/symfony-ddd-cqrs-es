<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Requesting;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\PSP\PaymentSession;
use Finance\Payment\Application\Requesting\PaymentRequester;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PaymentRequesterTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private PaymentRequester $service;
    private PaymentFinderInterface $finder;
    private \DateTimeImmutable $expiresAt;
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->service = new PaymentRequester(
            $this->uniqueValues,
            $this->finder,
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
        );
        $this->expiresAt = Clock::get()->now()->modify('+30 minutes');
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId)->toString();
        $reference = PaymentBuilder::sample('reference')->value;
        $hostedPageUrl = PaymentBuilder::sample('hostedPageUrl');
        $lines = $this->lines();
        $this->paymentGateway->expects(self::once())->method('requestPayment')
            ->with($paymentId, $checkoutSessionId, $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt)
            ->willReturn(new PaymentSession($reference, $hostedPageUrl));

        // When
        $result = $this->service->requestFor($checkoutSessionId, 4_200, 'EUR', $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);

        // Then
        self::assertSame($hostedPageUrl, $result);
        $payment = $this->finder->ofCheckoutSession($checkoutSessionId);
        self::assertSame($reference, $payment->reference);
        self::assertSame(PaymentStatus::REQUESTED, $payment->status);
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyClaimed(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);
        $this->uniqueValues->claim(
            UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION),
            $paymentBuilder['checkoutSessionId'],
            $payment->id->toString(),
        );
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // When
        $hostedPageUrl = $this->service->requestFor($paymentBuilder['checkoutSessionId'], 4_200, 'EUR', $this->lines(), 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);

        // Then
        self::assertSame($paymentBuilder['hostedPageUrl'], $hostedPageUrl);
    }

    /**
     * @return list<PaymentLine>
     */
    private function lines(): array
    {
        return [
            new PaymentLine(Label::fromString('Espresso cups, set of 6'), Money::fromCents(4_200, 'EUR'), Quantity::of(1)),
        ];
    }
}
