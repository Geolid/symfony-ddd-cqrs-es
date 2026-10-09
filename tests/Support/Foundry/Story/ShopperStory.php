<?php

declare(strict_types=1);

namespace Support\Foundry\Story;

use Support\Foundry\CartFactory;
use Support\Foundry\CustomerFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Zenstruck\Foundry\Attribute\AsFixture;

/** SPIKE — Identity (Iam) → Customer (Crm) → Cart (Shopping), linked by ids across three BCs. */
#[AsFixture(name: 'shoppers', groups: ['demo'])]
final class ShopperStory extends AbstractAggregateStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();
        $customer = CustomerFactory::new()->with(['identityId' => $identity->id->toString()])->shippingAddressDefined()->create();
        $cart = CartFactory::new()->with(['customerId' => $customer->aggregateRootId()->toString()])->productAdded()->productAdded()->create();

        $this->persist($identity, $customer, $cart);

        $this->addState('identity', $identity, pool: 'identities');
        $this->addState('customer', $customer);
        $this->addState('cart', $cart);
    }
}
