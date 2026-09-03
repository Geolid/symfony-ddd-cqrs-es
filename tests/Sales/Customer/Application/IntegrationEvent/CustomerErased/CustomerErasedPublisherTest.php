<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerErased;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = CustomerBuilder::new()->erased();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerErasedIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame($builder['erasedAt']->format(\DateTimeInterface::ATOM), $event->erasedAt->format(\DateTimeInterface::ATOM));
    }
}
