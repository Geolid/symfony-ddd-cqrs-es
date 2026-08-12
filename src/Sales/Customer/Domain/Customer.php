<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Domain\ValueObject\Email;

#[Aggregate('sales.customer.customer')]
final class Customer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private CustomerId $id;
    private Email $email;
    private bool $erased;

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public static function register(CustomerId $id, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new CustomerRegistered(
            id: $id->toString(),
            email: $email->toString(),
            registeredAt: $registeredAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if ($this->erased) {
            return;
        }

        $this->recordThat(new CustomerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyCustomerRegistered(CustomerRegistered $event): void
    {
        $this->id = CustomerId::fromString($event->id);
        $this->email = Email::fromString($event->email);
        $this->erased = false;
    }

    #[Apply]
    private function applyCustomerErased(CustomerErased $event): void
    {
        $this->erased = true;
    }
}
