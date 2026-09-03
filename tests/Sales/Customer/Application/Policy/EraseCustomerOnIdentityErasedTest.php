<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Policy\EraseCustomerOnIdentityErased;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseCustomerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $customer = CustomerBuilder::new()->withId($id)->create();
        $this->store($customer);

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->trigger(EraseCustomerOnIdentityErased::class, new IdentityErasedIntegrationEvent($id, Clock::get()->now()));
        $this->service(CustomerFinderInterface::class)->ofId($id);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(EraseCustomerOnIdentityErased::class, new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
