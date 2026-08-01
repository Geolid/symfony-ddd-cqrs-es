<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Support\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsTheAddressOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();
        $this->store($customer);

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new CustomerErased($customer->id()->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        self::assertSame('erased@erased.invalid', $this->registeredEventOf($customer->id()->toString())->email);
    }

    private function registeredEventOf(string $id): CustomerRegistered
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof CustomerRegistered && $event->id === $id) {
                return $event;
            }
        }

        self::fail('CustomerRegistered event not found in the stream.');
    }
}
