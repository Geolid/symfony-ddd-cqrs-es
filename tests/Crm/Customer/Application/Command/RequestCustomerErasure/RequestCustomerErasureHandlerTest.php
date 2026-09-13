<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\RequestCustomerErasure;

use Crm\Customer\Application\Command\RequestCustomerErasure\RequestCustomerErasure;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestCustomerErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);

        // When
        $this->dispatch(new RequestCustomerErasure($customer->id->toString()));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new RequestCustomerErasure($id));
    }
}
