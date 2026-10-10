<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Support\Factory;

use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\MoneyFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: PaymentId,
 *     checkoutSessionId: string,
 *     orderId: string,
 *     amount: Money,
 *     reference: PaymentReference,
 *     hostedPageUrl: string,
 *     requestedAt: \DateTimeImmutable,
 *     authorizedAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 *     capturedAt: \DateTimeImmutable,
 *     abandonedAt: \DateTimeImmutable,
 *     voidedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Payment, Inputs>
 */
final class PaymentFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Payment::class;
    }

    public function withCheckoutSessionId(string $checkoutSessionId): self
    {
        return $this->with(['checkoutSessionId' => $checkoutSessionId]);
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return $this->with(['amount' => MoneyFactory::new(['cents' => $amountInCents])->create()]);
    }

    public function withReference(PaymentReference $reference): self
    {
        return $this->with(['reference' => $reference]);
    }

    public function withHostedPageUrl(string $hostedPageUrl): self
    {
        return $this->with(['hostedPageUrl' => $hostedPageUrl]);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->with(['requestedAt' => $requestedAt]);
    }

    public function authorized(?\DateTimeImmutable $authorizedAt = null): self
    {
        return $this->with(array_filter(['authorizedAt' => $authorizedAt]))->transition(
            static function (Payment $payment, array $inputs): void {
                $payment->authorize($inputs['authorizedAt']);
            },
        );
    }

    public function failed(?string $orderId = null, ?\DateTimeImmutable $failedAt = null): self
    {
        return $this->with(array_filter(['orderId' => $orderId, 'failedAt' => $failedAt]))->transition(
            static function (Payment $payment, array $inputs): void {
                $payment->fail($inputs['orderId'], $inputs['failedAt']);
            },
        );
    }

    public function captured(?string $orderId = null, ?\DateTimeImmutable $capturedAt = null): self
    {
        return $this->with(array_filter(['orderId' => $orderId, 'capturedAt' => $capturedAt]))->transition(
            static function (Payment $payment, array $inputs): void {
                $payment->capture($inputs['orderId'], $inputs['capturedAt']);
            },
        );
    }

    public function abandoned(?\DateTimeImmutable $abandonedAt = null): self
    {
        return $this->with(array_filter(['abandonedAt' => $abandonedAt]))->transition(
            static function (Payment $payment, array $inputs): void {
                $payment->abandon($inputs['abandonedAt']);
            },
        );
    }

    public function voided(?\DateTimeImmutable $voidedAt = null): self
    {
        return $this->with(array_filter(['voidedAt' => $voidedAt]))->transition(
            static function (Payment $payment, array $inputs): void {
                $payment->void($inputs['voidedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Payment::request(
            id: $parameters['id'],
            checkoutSessionId: $parameters['checkoutSessionId'],
            amount: $parameters['amount'],
            reference: $parameters['reference'],
            hostedPageUrl: $parameters['hostedPageUrl'],
            requestedAt: $parameters['requestedAt'],
        );
    }

    protected function initialize(): static
    {
        // The id derives from the FINAL checkoutSessionId, so a with(['checkoutSessionId' => ...]) override carries over.
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['checkoutSessionId']);
            $parameters['id'] ??= PaymentId::forCheckoutSession($parameters['checkoutSessionId']);

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'checkoutSessionId' => Uuid::uuid7()->toString(),
            'orderId' => Uuid::uuid7()->toString(),
            'amount' => MoneyFactory::new(),
            'reference' => PaymentReferenceFactory::new(),
            'hostedPageUrl' => 'https://checkout.globex.test/pay/'.faker()->regexify('[A-Z0-9]{8}'),
            'requestedAt' => $now,
            'authorizedAt' => $now->modify('+1 day'),
            'failedAt' => $now->modify('+1 day'),
            'capturedAt' => $now->modify('+2 day'),
            'abandonedAt' => $now->modify('+1 day'),
            'voidedAt' => $now->modify('+1 day'),
        ];
    }
}
