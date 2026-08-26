<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerErased;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class CustomerErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->erased()->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerErasedIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
    }
}
