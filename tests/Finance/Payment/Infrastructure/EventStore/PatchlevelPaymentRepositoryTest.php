<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\EventStore;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelPaymentRepositoryTest extends AbstractIntegrationTestCase
{
    private PaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PaymentRepositoryInterface::class);
    }

    #[Test]
    #[DataProvider('provideLifecycle')]
    public function itSavesAndLoads(PaymentFactory $factory): void
    {
        // Given
        $payment = $factory->create();

        // When
        $this->repository->save($payment);
        $loaded = $this->repository->load($payment->id);

        // Then
        self::assertSame($this->propertiesOf($payment), $this->propertiesOf($loaded));
    }

    /**
     * @return iterable<string, array{PaymentFactory}>
     */
    public static function provideLifecycle(): iterable
    {
        yield 'captured' => [PaymentFactory::new()->authorized()->captured()];
        yield 'failed' => [PaymentFactory::new()->failed()];
        yield 'abandoned' => [PaymentFactory::new()->abandoned()];
        yield 'voided' => [PaymentFactory::new()->abandoned()->voided()];
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $payment = PaymentFactory::new()
            ->create();
        $this->store($payment);
        $duplicate = PaymentFactory::new()
            ->withCheckoutSessionId($payment->checkoutSessionId)
            ->create();

        // Then
        $this->expectException(PaymentAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->repository->load(PaymentIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $orderPayment = PaymentFactory::new()->create();
        $this->store($orderPayment);

        // When
        $exists = $this->repository->has($orderPayment->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(PaymentIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Payment $payment): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $payment->id->toString(),
            'reference' => $payment->reference->value,
            'checkoutSessionId' => $payment->checkoutSessionId,
            'amount' => ['cents' => $payment->amount->cents, 'currency' => $payment->amount->currency->value],
            'hostedPageUrl' => $payment->hostedPageUrl,
            'requestedAt' => $atom($payment->requestedAt),
            'operationalState' => $payment->operationalState->value,
            'authorizedAt' => $atom($payment->authorizedAt),
            'orderId' => $payment->orderId,
            'failedAt' => $atom($payment->failedAt),
            'capturedAt' => $atom($payment->capturedAt),
            'abandonedAt' => $atom($payment->abandonedAt),
            'voidedAt' => $atom($payment->voidedAt),
        ];
    }
}
