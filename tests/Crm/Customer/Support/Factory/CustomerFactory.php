<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Support\Factory;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Inputs = array{
 *     id: CustomerId,
 *     identityId: string,
 *     registeredAt: \DateTimeImmutable,
 *     shippingAddress: PostalAddress,
 *     shippingAddressDefinedAt: \DateTimeImmutable,
 *     billingAddress: PostalAddress,
 *     billingAddressDefinedAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Customer, Inputs>
 */
final class CustomerFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Customer::class;
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->with(['registeredAt' => $registeredAt]);
    }

    public function shippingAddressDefined(?PostalAddress $shippingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        return $this->with(array_filter(['shippingAddress' => $shippingAddress, 'shippingAddressDefinedAt' => $definedAt]))->transition(
            static function (Customer $customer, array $inputs): void {
                $customer->defineShippingAddress($inputs['shippingAddress'], $inputs['shippingAddressDefinedAt']);
            },
        );
    }

    public function billingAddressDefined(?PostalAddress $billingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        return $this->with(array_filter(['billingAddress' => $billingAddress, 'billingAddressDefinedAt' => $definedAt]))->transition(
            static function (Customer $customer, array $inputs): void {
                $customer->defineBillingAddress($inputs['billingAddress'], $inputs['billingAddressDefinedAt']);
            },
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        return $this->with(array_filter(['requestedAt' => $requestedAt]))->transition(
            static function (Customer $customer, array $inputs): void {
                $customer->requestErasure($inputs['requestedAt']);
            },
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        return $this->with(array_filter(['cancelledAt' => $cancelledAt]))->transition(
            static function (Customer $customer, array $inputs): void {
                $customer->cancelErasure($inputs['cancelledAt']);
            },
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        return $this->with(array_filter(['erasedAt' => $erasedAt]))->transition(
            static function (Customer $customer, array $inputs): void {
                $customer->erase($inputs['erasedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Customer::register(
            id: $parameters['id'],
            registeredAt: $parameters['registeredAt'],
        );
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['identityId']);
            $parameters['id'] ??= CustomerId::forIdentity($parameters['identityId']);

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'identityId' => Uuid::uuid7()->toString(),
            'registeredAt' => $now,
            'shippingAddress' => PostalAddressFactory::new(),
            'shippingAddressDefinedAt' => $now->modify('+1 day'),
            'billingAddress' => PostalAddressFactory::new(),
            'billingAddressDefinedAt' => $now->modify('+1 day'),
            'requestedAt' => $now->modify('+1 day'),
            'cancelledAt' => $now->modify('+2 days'),
            'erasedAt' => $now->modify('+2 day'),
        ];
    }
}
