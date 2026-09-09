<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Buyer\Domain\Event\BuyerBillingAddressDefined;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerErasureCancelled;
use Sales\Buyer\Domain\Event\BuyerErasureRequested;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressDefined;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.buyer.buyer')]
final class Buyer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::REQUESTED],
        ErasureState::REQUESTED->value => [ErasureState::RETAINED, ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) BuyerId $id;
    public private(set) string $identityId;
    public private(set) Email $email;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private ErasureState $erasureState;

    public static function register(BuyerId $id, string $identityId, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new BuyerRegistered(
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

        $this->recordThat(new BuyerShippingAddressDefined(
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

        $this->recordThat(new BuyerBillingAddressDefined(
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

        $this->recordThat(new BuyerErasureRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::RETAINED)) {
            return;
        }

        $this->recordThat(new BuyerErasureCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::ERASED)) {
            return;
        }

        $this->recordThat(new BuyerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    #[Apply]
    private function applyRegistered(BuyerRegistered $event): void
    {
        $this->id = BuyerId::fromString($event->id);
        $this->identityId = $event->identityId;
        $this->email = $event->email;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyShippingAddressDefined(BuyerShippingAddressDefined $event): void
    {
        $this->shippingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyBillingAddressDefined(BuyerBillingAddressDefined $event): void
    {
        $this->billingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErasureRequested(BuyerErasureRequested $event): void
    {
        $this->erasureState = ErasureState::REQUESTED;
    }

    #[Apply]
    private function applyErasureCancelled(BuyerErasureCancelled $event): void
    {
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyErased(BuyerErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }
}
