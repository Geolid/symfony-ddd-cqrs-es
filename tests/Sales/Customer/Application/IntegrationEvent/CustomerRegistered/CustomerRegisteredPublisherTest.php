<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerRegistered\CustomerRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = CustomerBuilder::new();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerRegisteredIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame($builder['email']->value, $event->email);
        self::assertSame($builder['registeredAt']->format(\DateTimeInterface::ATOM), $event->registeredAt->format(\DateTimeInterface::ATOM));
    }
}
