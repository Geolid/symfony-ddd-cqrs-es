<?php

declare(strict_types=1);

namespace Support\Foundry\Story;

use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Zenstruck\Foundry\Attribute\AsFixture;

/** SPIKE — same scenario as ShopperStory, composed from the existing test Builders (no Foundry factory). */
#[AsFixture(name: 'shoppers-builders', groups: ['demo'])]
final class BuilderShopperStory extends AbstractAggregateStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();
        $customer = CustomerBuilder::new(['identityId' => $identity->id->toString()])->shippingAddressDefined()->create();
        $cart = CartBuilder::new(['customerId' => (string) $customer->aggregateRootId()])->productAdded()->productAdded()->create();

        $this->persist($identity, $customer, $cart);

        $this->addState('identity', $identity, pool: 'identities');
    }
}
