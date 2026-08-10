<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Domain\ValueObject\Email;

final class CustomerTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        $id = CustomerId::generate();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Customer::register($id, Email::fromString('Buyer@Example.COM'), $registeredAt))
            ->then(new CustomerRegistered($id->toString(), 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itErases(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Customer $customer) => $customer->erase($erasedAt))
            ->then(new CustomerErased($id, $erasedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotEraseAnAlreadyErased(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Customer $customer) => $customer->erase(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }
}
