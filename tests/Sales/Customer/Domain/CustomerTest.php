<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\CustomerId;
use Sales\Customer\Domain\Email;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerIdentityLinked;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Exception\CustomerAlreadyLinkedToIdentityException;

final class CustomerTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegistersACustomer(): void
    {
        $id = CustomerId::generate();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Customer::register($id, Email::fromString('Buyer@Example.COM'), $registeredAt))
            ->then(new CustomerRegistered($id->toString(), 'buyer@example.com', $registeredAt->format('c')));
    }

    #[Test]
    public function itErasesACustomer(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format('c')))
            ->when(static fn (Customer $customer) => $customer->erase($erasedAt))
            ->then(new CustomerErased($id, $erasedAt->format('c')));
    }

    #[Test]
    public function itDoesNotEraseAnAlreadyErasedCustomer(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format('c')),
                new CustomerErased($id, $erasedAt->format('c')),
            )
            ->when(static fn (Customer $customer) => $customer->erase(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itLinksAnIdentity(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $identityId = 'identity-1';

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format('c')))
            ->when(static fn (Customer $customer) => $customer->linkIdentity($identityId))
            ->then(new CustomerIdentityLinked($id, $identityId));
    }

    #[Test]
    public function itCannotLinkAnIdentityTwice(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format('c')),
                new CustomerIdentityLinked($id, 'identity-1'),
            )
            ->when(static fn (Customer $customer) => $customer->linkIdentity('identity-2'))
            ->expectsException(CustomerAlreadyLinkedToIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }
}
