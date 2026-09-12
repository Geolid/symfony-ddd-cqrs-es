<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\CancelCustomerErasure;

use Crm\Customer\Application\Command\CancelCustomerErasure\CancelCustomerErasure;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelCustomerErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $customer = CustomerBuilder::new()->erasureRequested()->create();
        $this->store($customer);

        // When
        $this->dispatch(new CancelCustomerErasure($customer->id->toString()));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new CancelCustomerErasure($id));
    }
}
