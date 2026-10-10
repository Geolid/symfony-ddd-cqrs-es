<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Pii;

use Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined\CustomerBillingAddressDefinedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerShippingAddressDefined\CustomerShippingAddressDefinedIntegrationEvent;
use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $erased = $this->storedEventOf(CustomerShippingAddressDefined::class, $customer->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->postalAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()
            ->billingAddressDefined()
            ->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $erased = $this->storedEventOf(CustomerBillingAddressDefined::class, $customer->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->postalAddress));
    }

    #[Test]
    public function itCryptoShredsCustomerShippingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()->shippingAddressDefined()->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $erased = $this->storedEventOf(CustomerShippingAddressDefinedIntegrationEvent::class, $customer->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), $erased->postalAddress);
    }

    #[Test]
    public function itCryptoShredsCustomerBillingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()->billingAddressDefined()->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $erased = $this->storedEventOf(CustomerBillingAddressDefinedIntegrationEvent::class, $customer->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), $erased->postalAddress);
    }
}
