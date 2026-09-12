<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Event\ShopperBillingAddressDefined;
use Shopping\Checkout\Domain\Event\ShopperErased;
use Shopping\Checkout\Domain\Event\ShopperErasureCancelled;
use Shopping\Checkout\Domain\Event\ShopperErasureRequested;
use Shopping\Checkout\Domain\Event\ShopperRegistered;
use Shopping\Checkout\Domain\Event\ShopperShippingAddressDefined;
use Shopping\Checkout\Domain\ValueObject\Email;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[Aggregate('shopping.checkout.shopper')]
final class Shopper implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::REQUESTED],
        ErasureState::REQUESTED->value => [ErasureState::RETAINED, ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) ShopperId $id;
    public private(set) string $identityId;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private ErasureState $erasureState;

    public static function register(ShopperId $id, string $identityId, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new ShopperRegistered(
            id: $id->toString(),
            identityId: $identityId,
            email: $email,
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function defineShippingAddress(PostalAddress $shippingAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->shippingAddress?->equals($shippingAddress)) {
            return;
        }

        $this->recordThat(new ShopperShippingAddressDefined(
            id: $this->id->toString(),
            identityId: $this->identityId,
            postalAddress: $shippingAddress,
            definedAt: $definedAt,
        ));
    }

    public function defineBillingAddress(PostalAddress $billingAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->billingAddress?->equals($billingAddress)) {
            return;
        }

        $this->recordThat(new ShopperBillingAddressDefined(
            id: $this->id->toString(),
            identityId: $this->identityId,
            postalAddress: $billingAddress,
            definedAt: $definedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::REQUESTED)) {
            return;
        }

        $this->recordThat(new ShopperErasureRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::RETAINED)) {
            return;
        }

        $this->recordThat(new ShopperErasureCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::ERASED)) {
            return;
        }

        $this->recordThat(new ShopperErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    #[Apply]
    private function applyRegistered(ShopperRegistered $event): void
    {
        $this->id = ShopperId::fromString($event->id);
        $this->identityId = $event->identityId;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyShippingAddressDefined(ShopperShippingAddressDefined $event): void
    {
        $this->shippingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyBillingAddressDefined(ShopperBillingAddressDefined $event): void
    {
        $this->billingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErasureRequested(ShopperErasureRequested $event): void
    {
        $this->erasureState = ErasureState::REQUESTED;
    }

    #[Apply]
    private function applyErasureCancelled(ShopperErasureCancelled $event): void
    {
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyErased(ShopperErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }
}
