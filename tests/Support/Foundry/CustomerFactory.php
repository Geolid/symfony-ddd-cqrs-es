<?php

declare(strict_types=1);

namespace Support\Foundry;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * SPIKE — throwaway.
 *
 * @phpstan-type Inputs = array{
 *     id: CustomerId,
 *     identityId: string,
 *     registeredAt: \DateTimeImmutable,
 *     shippingAddress: PostalAddress,
 *     shippingAddressDefinedAt: \DateTimeImmutable,
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

    public function shippingAddressDefined(?PostalAddress $shippingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        $factory = $this->with(array_filter(
            ['shippingAddress' => $shippingAddress, 'shippingAddressDefinedAt' => $definedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $factory->transition(
            static fn (Customer $customer, array $parameters) => $customer->defineShippingAddress($parameters['shippingAddress'], $parameters['shippingAddressDefinedAt']),
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Customer::register(id: $parameters['id'], registeredAt: $parameters['registeredAt']);
    }

    protected function initialize(): static
    {
        // id derives from the FINAL identityId, so a with(['identityId' => ...]) override carries over.
        return parent::initialize()->beforeInstantiate(
            static function (array $parameters): array {
                Assert::string($parameters['identityId']);
                $parameters['id'] ??= CustomerId::forIdentity($parameters['identityId']);

                return $parameters;
            },
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'identityId' => Uuid::uuid7()->toString(),
            'registeredAt' => $now,
            'shippingAddress' => PostalAddress::of(
                faker()->name(),
                Address::of(faker()->streetAddress(), faker()->postcode(), faker()->city(), faker()->countryCode()),
            ),
            'shippingAddressDefinedAt' => $now->modify('+1 day'),
        ];
    }
}
