<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerErased;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CustomerErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $erasedAt = Clock::get()->now();
        $customer = CustomerBuilder::new()->erased($erasedAt)->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerErasedIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame($erasedAt->format(\DateTimeImmutable::ATOM), $event->erasedAt->format(\DateTimeImmutable::ATOM));
    }
}
