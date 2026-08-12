<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Processor\ReleaseEmailOnCustomerErased;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\ValueObject\CustomerUniqueValue;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ReleaseEmailOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    private ReleaseEmailOnCustomerErased $processor;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReleaseEmailOnCustomerErased::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleasesTheEmailOnCustomerErased(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();
        $fingerprint = $customer->email()->fingerprint();
        $this->uniqueValues->reserve(CustomerUniqueValue::EMAIL, $fingerprint);

        // When
        ($this->processor)(new CustomerErased($customer->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(CustomerUniqueValue::EMAIL, $fingerprint));
    }
}
