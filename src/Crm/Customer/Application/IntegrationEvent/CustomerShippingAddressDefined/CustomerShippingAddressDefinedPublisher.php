<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerShippingAddressDefined;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;

#[Publisher('crm.customer.publish_customer_shipping_address_defined')]
final readonly class CustomerShippingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerShippingAddressDefined::class)]
    public function __invoke(CustomerShippingAddressDefined $event): void
    {
        $this->publisher->publish(Customer::class, $event->id->toString(), new CustomerShippingAddressDefinedIntegrationEvent(
            customerId: $event->id->toString(),
            postalAddress: PostalAddressMapper::toArray($event->postalAddress),
            definedAt: $event->definedAt,
        ));
    }
}
