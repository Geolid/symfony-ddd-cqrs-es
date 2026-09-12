<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Application\Policy\EraseCustomerOnErasureApproved;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseCustomerOnErasureApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $builder = CustomerBuilder::new()->erasureRequested();
        $customer = $builder->create();
        $this->store($customer);

        // When
        $this->trigger(EraseCustomerOnErasureApproved::class, new ErasureApprovedIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        self::assertNull($this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString()));
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(EraseCustomerOnErasureApproved::class, new ErasureApprovedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
