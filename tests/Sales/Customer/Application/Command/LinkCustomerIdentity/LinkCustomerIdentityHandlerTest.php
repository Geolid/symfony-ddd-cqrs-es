<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\LinkCustomerIdentity;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\LinkCustomerIdentity\LinkCustomerIdentity;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\Exception\CustomerAlreadyLinkedToIdentityException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class LinkCustomerIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLinksAnIdentityToACustomer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->create();
        $this->store($customer);

        // When
        $this->dispatch(new LinkCustomerIdentity($customer->id()->toString(), 'identity-1'));

        // Then
        $results = array_values(iterator_to_array($this->service(CustomerFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame('identity-1', $results[0]->identityId);
    }

    #[Test]
    public function itFailsWhenTheCustomerDoesNotExist(): void
    {
        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new LinkCustomerIdentity(CustomerId::generate()->toString(), 'identity-1'));
    }

    #[Test]
    public function itFailsWhenTheCustomerIsAlreadyLinked(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->linkedToIdentity('identity-1')->create();
        $this->store($customer);

        // Then
        $this->expectException(CustomerAlreadyLinkedToIdentityException::class);

        // When
        $this->dispatch(new LinkCustomerIdentity($customer->id()->toString(), 'identity-2'));
    }
}
