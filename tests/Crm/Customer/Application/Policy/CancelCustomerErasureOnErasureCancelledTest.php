<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Application\Policy\CancelCustomerErasureOnErasureCancelled;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelCustomerErasureOnErasureCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $builder = CustomerBuilder::new()->erasureRequested();
        $customer = $builder->create();
        $this->store($customer);

        // When
        $this->trigger(CancelCustomerErasureOnErasureCancelled::class, new ErasureCancelledIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(CancelCustomerErasureOnErasureCancelled::class, new ErasureCancelledIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
