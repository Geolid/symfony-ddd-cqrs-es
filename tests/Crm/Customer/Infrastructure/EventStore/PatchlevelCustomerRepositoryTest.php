<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\EventStore;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Crm\Tests\Customer\Support\Factory\CustomerIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCustomerRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $customer = CustomerFactory::new()
            ->shippingAddressDefined()
            ->billingAddressDefined()
            ->erasureRequested()
            ->erasureCancelled()
            ->erasureRequested()
            ->erased()
            ->create();

        // When
        $this->repository->save($customer);
        $loaded = $this->repository->load($customer->id);

        // Then
        self::assertSame($this->propertiesOf($customer), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->repository->load(CustomerIdFactory::new()->create());
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customer = CustomerFactory::new()->withIdentityId($identityId)->create();
        $this->store($customer);
        $duplicate = CustomerFactory::new()->withIdentityId($identityId)->create();

        // Then
        $this->expectException(CustomerAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $customer = CustomerFactory::new()->create();
        $this->store($customer);

        // When
        $exists = $this->repository->has($customer->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(CustomerIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Customer $customer): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);
        $address = static fn (?PostalAddress $postalAddress): ?array => null === $postalAddress ? null : PostalAddressMapper::toArray($postalAddress);

        return [
            'id' => $customer->id->toString(),
            'registeredAt' => $atom($customer->registeredAt),
            'shippingAddress' => $address($customer->shippingAddress),
            'shippingAddressDefinedAt' => $atom($customer->shippingAddressDefinedAt),
            'billingAddress' => $address($customer->billingAddress),
            'billingAddressDefinedAt' => $atom($customer->billingAddressDefinedAt),
            'erasureState' => $customer->erasureState->value,
            'erasureRequestedAt' => $atom($customer->erasureRequestedAt),
            'erasureCancelledAt' => $atom($customer->erasureCancelledAt),
            'erasedAt' => $atom($customer->erasedAt),
        ];
    }
}
