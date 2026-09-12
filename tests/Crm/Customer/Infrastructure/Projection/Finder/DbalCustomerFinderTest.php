<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Projection\Finder;

use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Application\Finder\Customer\PostalAddressResult;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCustomerFinderTest extends AbstractIntegrationTestCase
{
    private CustomerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CustomerFinderInterface::class);
    }

    #[Test]
    public function itFinds(): void
    {
        // Given
        $other = CustomerBuilder::new()->create();
        $builder = CustomerBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $customer = $builder->create();
        $this->store($other, $customer);

        // When
        $found = $this->finder->ofIdOrNull($customer->id->toString());
        $notFound = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($found);
        self::assertSame($customer->id->toString(), $found->id);
        self::assertSame($builder['firstName']->value, $found->firstName);
        self::assertSame($builder['lastName']->value, $found->lastName);
        self::assertSame($builder['email']->value, $found->email);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
            $found->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNotNull($found->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $this->toArray($found->shippingAddress));
        self::assertNotNull($found->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $this->toArray($found->billingAddress));
        self::assertSame(ErasureStatus::RETAINED, $found->erasureStatus);
        self::assertNull($notFound);
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertNotNull($result);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsWithErasureRequested(): void
    {
        // Given
        $customer = CustomerBuilder::new()->erasureRequested()->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function toArray(PostalAddressResult $postalAddressResult): array
    {
        return [
            'recipientName' => $postalAddressResult->recipientName,
            'address' => [
                'street' => $postalAddressResult->address->street,
                'postalCode' => $postalAddressResult->address->postalCode,
                'city' => $postalAddressResult->address->city,
                'countryCode' => $postalAddressResult->address->countryCode,
            ],
        ];
    }
}
