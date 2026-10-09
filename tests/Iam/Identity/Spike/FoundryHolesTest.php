<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Spike;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shopping\Cart\Domain\Cart;
use Support\Foundry\CartFactory;
use Support\Foundry\CustomerFactory;

/**
 * SPIKE — throwaway. One test per hole found in the Builder usage inventory.
 */
final class FoundryHolesTest extends TestCase
{
    #[Test]
    public function itDerivesIdFromOverriddenKey(): void
    {
        $identityId = Uuid::uuid7()->toString();

        $customer = CustomerFactory::new()->with(['identityId' => $identityId])->create();

        self::assertSame(CustomerId::forIdentity($identityId)->toString(), $customer->aggregateRootId()->toString());
    }

    #[Test]
    public function itKeepsTimelineCoherentAndMonotonicAcrossCreates(): void
    {
        $first = CustomerFactory::inputs(CustomerFactory::new()->create());
        $second = CustomerFactory::inputs(CustomerFactory::new()->create());

        self::assertSame(
            $first['registeredAt']->modify('+1 day')->format(\DateTimeInterface::ATOM),
            $first['shippingAddressDefinedAt']->format(\DateTimeInterface::ATOM),
        );
        self::assertGreaterThan($first['registeredAt'], $second['registeredAt']);
    }

    #[Test]
    public function itAccumulatesAcrossTransitions(): void
    {
        $factory = CartFactory::new()->productAdded()->productAdded();
        $cart = $factory->productRemoved()->create();

        self::assertCount(4, $cart->releaseEvents());
        self::assertCount(3, $factory->create()->releaseEvents());
    }

    #[Test]
    public function itCreatesMany(): void
    {
        $carts = CartFactory::new()->many(3)->create();

        $startedAts = array_map(static fn (Cart $cart): mixed => CartFactory::inputs($cart)['startedAt'], $carts);

        self::assertCount(3, $carts);
        self::assertSame(3, \count(array_unique(array_map(static fn (Cart $cart): string => $cart->aggregateRootId()->toString(), $carts))));
        self::assertTrue($startedAts[0] < $startedAts[1] && $startedAts[1] < $startedAts[2]);
    }

    #[Test]
    public function itSurvivesUniqueAcrossManyIdentities(): void
    {
        $identities = IdentityFactory::new()->many(200)->create();

        self::assertCount(200, $identities);
    }
}
