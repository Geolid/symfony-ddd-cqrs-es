<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Policy\ApproveOrdersErasureOnCustomerErased;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveOrdersErasureOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        $this->trigger(ApproveOrdersErasureOnCustomerErased::class, new CustomerErasedIntegrationEvent($customerId, Clock::get()->now()));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byCustomer($customerId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
