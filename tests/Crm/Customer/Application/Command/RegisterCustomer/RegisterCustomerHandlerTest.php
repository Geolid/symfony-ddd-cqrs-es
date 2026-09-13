<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\RegisterCustomer;

use Crm\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
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
        $firstName = CustomerBuilder::sample('firstName');
        $lastName = CustomerBuilder::sample('lastName');
        $email = CustomerBuilder::sample('email');

        // When
        $this->dispatch(new RegisterCustomer(
            identityId: $identityId,
            firstName: $firstName->value,
            lastName: $lastName->value,
            email: $email->value,
        ));

        // Then
        $id = CustomerId::forIdentity($identityId)->toString();
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($id);
        self::assertNotNull($result);
        self::assertSame($id, $result->id);
        self::assertSame($firstName->value, $result->firstName);
        self::assertSame($lastName->value, $result->lastName);
        self::assertSame($email->value, $result->email);
    }
}
