<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;

#[Publisher('crm.customer.publish_customer_billing_address_defined')]
final readonly class CustomerBillingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerBillingAddressDefined::class)]
    public function __invoke(CustomerBillingAddressDefined $event): void
    {
        $this->publisher->publish(Customer::class, $event->id->toString(), new CustomerBillingAddressDefinedIntegrationEvent(
            customerId: $event->id->toString(),
            postalAddress: PostalAddressMapper::toArray($event->postalAddress),
            definedAt: $event->definedAt,
        ));
    }
}
