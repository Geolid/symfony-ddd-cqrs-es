<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Requesting;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\PSP\PaymentSession;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestAlreadyExpiredException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestCurrencyMismatchException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestInvalidUrlException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestWithoutLineException;
use Finance\Payment\Application\Requesting\PaymentRequester;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
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
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->service = new PaymentRequester(
            $this->uniqueness,
            $this->finder,
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
            $this->service(ClockInterface::class),
        );
        $this->expiresAt = Clock::get()->now()->modify('+30 minutes');
    }

    #[Test]
    #[DataProvider('provideExpiry')]
    public function itRequests(\DateTimeImmutable $expiresAt): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId)->toString();
        $reference = PaymentReferenceFactory::new()->create()->value;
        $hostedPageUrl = \sprintf('https://checkout.globex.test/pay/%s', $reference);
        $lines = $this->lines();
        $this->paymentGateway->expects(self::once())->method('requestPayment')
            ->with($paymentId, $checkoutSessionId, $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $expiresAt)
            ->willReturn(new PaymentSession($reference, $hostedPageUrl));

        // When
        $result = $this->service->requestFor($checkoutSessionId, 'EUR', $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $expiresAt);

        // Then
        self::assertSame($hostedPageUrl, $result);
        $payment = $this->finder->ofCheckoutSession($checkoutSessionId);
        self::assertSame($reference, $payment->reference);
        self::assertSame(4_200, $payment->amountInCents);
        self::assertSame(PaymentStatus::REQUESTED, $payment->status);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable}>
     */
    public static function provideExpiry(): iterable
    {
        $now = Clock::get()->now();

        yield 'before expiry' => [$now->modify('+30 minutes')];
        yield 'at expiry' => [$now];
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyClaimed(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);
        $this->uniqueness->claim(
            UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION),
            $payment->checkoutSessionId,
            $payment->id->toString(),
        );
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // When
        $hostedPageUrl = $this->service->requestFor($payment->checkoutSessionId, 'EUR', $this->lines(), 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);

        // Then
        self::assertSame($payment->hostedPageUrl, $hostedPageUrl);
    }

    #[Test]
    public function itFailsWhenNoLine(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // Then
        $this->expectException(PaymentRequestWithoutLineException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'EUR', [], 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);
    }

    #[Test]
    public function itFailsWhenLinesCurrencyMismatch(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('requestPayment');
        $lines = [
            new PaymentLine(Label::fromString('Mug'), Money::fromCents(1_500, 'GBP'), Quantity::of(1)),
        ];

        // Then
        $this->expectException(PaymentRequestCurrencyMismatchException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'EUR', $lines, 'https://web.test/sales/orders', 'https://web.test/sales/cart', $this->expiresAt);
    }

    #[Test]
    public function itFailsWhenUrlInvalid(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // Then
        $this->expectException(PaymentRequestInvalidUrlException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'EUR', $this->lines(), 'not-a-url', 'https://web.test/sales/cart', $this->expiresAt);
    }

    #[Test]
    public function itFailsWhenAlreadyExpired(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('requestPayment');
        $expiredAt = Clock::get()->now()->modify('-1 second');

        // Then
        $this->expectException(PaymentRequestAlreadyExpiredException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'EUR', $this->lines(), 'https://web.test/sales/orders', 'https://web.test/sales/cart', $expiredAt);
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
