<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerBillingAddressDefined;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressDefined;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;

final class BuyerTest extends AggregateRootTestCase
{
    private BuyerId $id;
    private string $identityId;
    private Email $email;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = BuyerBuilder::sample('identityId');
        $this->id = BuyerId::forIdentity($this->identityId);
        $this->email = BuyerBuilder::sample('email');
        $this->registeredAt = BuyerBuilder::sample('registeredAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Buyer => Buyer::register($this->id, $this->identityId, $this->email, $this->registeredAt))
            ->then(new BuyerRegistered($this->id->toString(), $this->identityId, $this->email, $this->registeredAt));
    }

    #[Test]
    public function itDefinesShippingAddress(): void
    {
        $definedAt = BuyerBuilder::sample('shippingAddressDefinedAt');
        $shippingAddress = BuyerBuilder::sample('shippingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->defineShippingAddress($shippingAddress, $definedAt))
            ->then(new BuyerShippingAddressDefined(
                id: $this->id->toString(),
                identityId: $this->identityId,
                postalAddress: $shippingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalShippingAddress(): void
    {
        $shippingAddress = BuyerBuilder::sample('shippingAddress');
        $definedAt = BuyerBuilder::sample('shippingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new BuyerShippingAddressDefined($this->id->toString(), $this->identityId, $shippingAddress, $definedAt),
            )
            ->when(static fn (Buyer $buyer) => $buyer->defineShippingAddress($shippingAddress, BuyerBuilder::sample('shippingAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itDefinesBillingAddress(): void
    {
        $definedAt = BuyerBuilder::sample('billingAddressDefinedAt');
        $billingAddress = BuyerBuilder::sample('billingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->defineBillingAddress($billingAddress, $definedAt))
            ->then(new BuyerBillingAddressDefined(
                id: $this->id->toString(),
                identityId: $this->identityId,
                postalAddress: $billingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalBillingAddress(): void
    {
        $billingAddress = BuyerBuilder::sample('billingAddress');
        $definedAt = BuyerBuilder::sample('billingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new BuyerBillingAddressDefined($this->id->toString(), $this->identityId, $billingAddress, $definedAt),
            )
            ->when(static fn (Buyer $buyer) => $buyer->defineBillingAddress($billingAddress, BuyerBuilder::sample('billingAddressDefinedAt')))
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
        return new BuyerRegistered($this->id->toString(), $this->identityId, $this->email, $this->registeredAt);
    }
}
