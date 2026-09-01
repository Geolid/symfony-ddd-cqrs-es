<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerRegistered\CustomerRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CustomerRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $now = Clock::get()->now();
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com')->withRegisteredAt($now)->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerRegisteredIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame('buyer@example.com', $event->email);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->registeredAt->format(\DateTimeImmutable::ATOM));
    }
}
