<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer;

use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Crm\Customer\Domain\Customer\Event\CustomerErasureCancelled;
use Crm\Customer\Domain\Customer\Event\CustomerErasureRequested;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Domain\Customer\ValueObject\Email;
use Crm\Customer\Domain\Customer\ValueObject\Name;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('crm.customer.customer')]
final class Customer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::REQUESTED],
        ErasureState::REQUESTED->value => [ErasureState::RETAINED, ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];
    #[Id]
    public private(set) CustomerId $id;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private ErasureState $erasureState;

    public static function register(
        CustomerId $id,
        Name $firstName,
        Name $lastName,
        Email $email,
        \DateTimeImmutable $registeredAt,
    ): self {
        $self = new self();
        $self->recordThat(new CustomerRegistered(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
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

        $this->recordThat(new CustomerShippingAddressDefined(
            id: $this->id,
            postalAddress: $shippingAddress,
            definedAt: $definedAt,
        ));
    }

    public function defineBillingAddress(PostalAddress $billingAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->billingAddress?->equals($billingAddress)) {
            return;
        }

        $this->recordThat(new CustomerBillingAddressDefined(
            id: $this->id,
            postalAddress: $billingAddress,
            definedAt: $definedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::REQUESTED)) {
            return;
        }

        $this->recordThat(new CustomerErasureRequested(
            id: $this->id,
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::RETAINED)) {
            return;
        }

        $this->recordThat(new CustomerErasureCancelled(
            id: $this->id,
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::ERASED)) {
            return;
        }

        $this->recordThat(new CustomerErased(
            id: $this->id,
            erasedAt: $erasedAt,
        ));
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    #[Apply]
    private function applyRegistered(CustomerRegistered $event): void
    {
        $this->id = $event->id;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyShippingAddressDefined(CustomerShippingAddressDefined $event): void
    {
        $this->shippingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyBillingAddressDefined(CustomerBillingAddressDefined $event): void
    {
        $this->billingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErasureRequested(CustomerErasureRequested $event): void
    {
        $this->erasureState = ErasureState::REQUESTED;
    }

    #[Apply]
    private function applyErasureCancelled(CustomerErasureCancelled $event): void
    {
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyErased(CustomerErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }
}
