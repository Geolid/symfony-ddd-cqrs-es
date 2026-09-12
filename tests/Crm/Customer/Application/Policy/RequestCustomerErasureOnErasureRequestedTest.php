<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Application\Policy\RequestCustomerErasureOnErasureRequested;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestCustomerErasureOnErasureRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $builder = CustomerBuilder::new();
        $customer = $builder->create();
        $this->store($customer);

        // When
        $this->trigger(RequestCustomerErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(RequestCustomerErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
