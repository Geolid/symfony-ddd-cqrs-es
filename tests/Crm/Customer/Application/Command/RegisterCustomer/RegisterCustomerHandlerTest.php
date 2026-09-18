<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\RegisterCustomer;

use Crm\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RegisterCustomer($identityId));

        // Then
        $id = CustomerId::forIdentity($identityId)->toString();
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($id);
        self::assertNotNull($result);
        self::assertSame($id, $result->id);
    }
}
