<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Domain\Event\ShopperBillingAddressDefined;
use Shopping\Checkout\Domain\Event\ShopperErased;
use Shopping\Checkout\Domain\Event\ShopperErasureCancelled;
use Shopping\Checkout\Domain\Event\ShopperErasureRequested;
use Shopping\Checkout\Domain\Event\ShopperRegistered;
use Shopping\Checkout\Domain\Event\ShopperShippingAddressDefined;
use Shopping\Checkout\Domain\Shopper;
use Shopping\Checkout\Domain\ValueObject\Email;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;

final class ShopperTest extends AggregateRootTestCase
{
    private ShopperId $id;
    private string $identityId;
    private Email $email;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = ShopperBuilder::sample('identityId');
        $this->id = ShopperId::forIdentity($this->identityId);
        $this->email = ShopperBuilder::sample('email');
        $this->registeredAt = ShopperBuilder::sample('registeredAt');
        $this->requestedAt = ShopperBuilder::sample('requestedAt');
        $this->cancelledAt = ShopperBuilder::sample('cancelledAt');
        $this->erasedAt = ShopperBuilder::sample('erasedAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Shopper => Shopper::register($this->id, $this->identityId, $this->email, $this->registeredAt))
            ->then(new ShopperRegistered($this->id->toString(), $this->identityId, $this->email, $this->registeredAt));
    }

    #[Test]
    public function itDefinesShippingAddress(): void
    {
        $definedAt = ShopperBuilder::sample('shippingAddressDefinedAt');
        $shippingAddress = ShopperBuilder::sample('shippingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Shopper $shopper) => $shopper->defineShippingAddress($shippingAddress, $definedAt))
            ->then(new ShopperShippingAddressDefined(
                id: $this->id->toString(),
                identityId: $this->identityId,
                postalAddress: $shippingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalShippingAddress(): void
    {
        $shippingAddress = ShopperBuilder::sample('shippingAddress');
        $definedAt = ShopperBuilder::sample('shippingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new ShopperShippingAddressDefined($this->id->toString(), $this->identityId, $shippingAddress, $definedAt),
            )
            ->when(static fn (Shopper $shopper) => $shopper->defineShippingAddress($shippingAddress, ShopperBuilder::sample('shippingAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itDefinesBillingAddress(): void
    {
        $definedAt = ShopperBuilder::sample('billingAddressDefinedAt');
        $billingAddress = ShopperBuilder::sample('billingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Shopper $shopper) => $shopper->defineBillingAddress($billingAddress, $definedAt))
            ->then(new ShopperBillingAddressDefined(
                id: $this->id->toString(),
                identityId: $this->identityId,
                postalAddress: $billingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalBillingAddress(): void
    {
        $billingAddress = ShopperBuilder::sample('billingAddress');
        $definedAt = ShopperBuilder::sample('billingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new ShopperBillingAddressDefined($this->id->toString(), $this->identityId, $billingAddress, $definedAt),
            )
            ->when(static fn (Shopper $shopper) => $shopper->defineBillingAddress($billingAddress, ShopperBuilder::sample('billingAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Shopper $shopper) => $shopper->requestErasure($this->requestedAt))
            ->then(new ShopperErasureRequested($this->id->toString(), $this->requestedAt));
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyRequested(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(static fn (Shopper $shopper) => $shopper->requestErasure(ShopperBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Shopper $shopper) => $shopper->cancelErasure($this->cancelledAt))
            ->then(new ShopperErasureCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelErasureWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Shopper $shopper) => $shopper->cancelErasure(ShopperBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Shopper $shopper) => $shopper->erase($this->erasedAt))
            ->then(new ShopperErased($this->id->toString(), $this->erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Shopper $shopper) => $shopper->erase(ShopperBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $this
            ->given($this->registered(), $this->requested(), $this->erased())
            ->when(static fn (Shopper $shopper) => $shopper->erase(ShopperBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Shopper::class;
    }

    private function registered(): ShopperRegistered
    {
        return new ShopperRegistered($this->id->toString(), $this->identityId, $this->email, $this->registeredAt);
    }

    private function requested(): ShopperErasureRequested
    {
        return new ShopperErasureRequested($this->id->toString(), $this->requestedAt);
    }

    private function erased(): ShopperErased
    {
        return new ShopperErased($this->id->toString(), $this->erasedAt);
    }
}
