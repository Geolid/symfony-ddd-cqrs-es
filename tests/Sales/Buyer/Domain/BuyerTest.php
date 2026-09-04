<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerBillingAddressRegistered;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressRegistered;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final class BuyerTest extends AggregateRootTestCase
{
    private BuyerId $id;
    private Email $email;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = BuyerId::generate();
        $this->email = BuyerBuilder::sample('email');
        $this->registeredAt = BuyerBuilder::sample('registeredAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Buyer => Buyer::register($this->id, $this->email, $this->registeredAt))
            ->then(new BuyerRegistered($this->id->toString(), $this->email->value, $this->registeredAt));
    }

    #[Test]
    public function itRegistersShippingAddress(): void
    {
        $setAt = BuyerBuilder::sample('shippingAddressRegisteredAt');
        $shippingAddress = $this->shippingAddress();

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->registerShippingAddress($shippingAddress, $setAt))
            ->then(new BuyerShippingAddressRegistered(
                id: $this->id->toString(),
                address: $shippingAddress->toArray(),
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalShippingAddress(): void
    {
        $shippingAddress = $this->shippingAddress();
        $setAt = BuyerBuilder::sample('shippingAddressRegisteredAt');

        $this
            ->given(
                $this->registered(),
                new BuyerShippingAddressRegistered($this->id->toString(), $shippingAddress->toArray(), $setAt),
            )
            ->when(static fn (Buyer $buyer) => $buyer->registerShippingAddress($shippingAddress, BuyerBuilder::sample('shippingAddressRegisteredAt')))
            ->then();
    }

    #[Test]
    public function itRegistersBillingAddress(): void
    {
        $setAt = BuyerBuilder::sample('billingAddressRegisteredAt');
        $billingAddress = $this->billingAddress();

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->registerBillingAddress($billingAddress, $setAt))
            ->then(new BuyerBillingAddressRegistered(
                id: $this->id->toString(),
                address: $billingAddress->toArray(),
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalBillingAddress(): void
    {
        $billingAddress = $this->billingAddress();
        $setAt = BuyerBuilder::sample('billingAddressRegisteredAt');

        $this
            ->given(
                $this->registered(),
                new BuyerBillingAddressRegistered($this->id->toString(), $billingAddress->toArray(), $setAt),
            )
            ->when(static fn (Buyer $buyer) => $buyer->registerBillingAddress($billingAddress, BuyerBuilder::sample('billingAddressRegisteredAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $erasedAt = BuyerBuilder::sample('erasedAt');

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->erase($erasedAt))
            ->then(new BuyerErased($this->id->toString(), $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $erasedAt = BuyerBuilder::sample('erasedAt');

        $this
            ->given($this->registered(), new BuyerErased($this->id->toString(), $erasedAt))
            ->when(static fn (Buyer $buyer) => $buyer->erase(BuyerBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Buyer::class;
    }

    private function registered(): BuyerRegistered
    {
        return new BuyerRegistered($this->id->toString(), $this->email->value, $this->registeredAt);
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }
}
