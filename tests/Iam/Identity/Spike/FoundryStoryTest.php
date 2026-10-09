<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Spike;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Cart\Domain\Cart;
use Support\Foundry\Story\ShopperStory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * SPIKE — throwaway.
 */
final class FoundryStoryTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPersistsAggregatesOfThreeBoundedContexts(): void
    {
        self::bootKernel();
        $repositories = $this->service(RepositoryManager::class);

        $identity = ShopperStory::get('identity');
        $customer = ShopperStory::get('customer');
        $cart = ShopperStory::get('cart');

        self::assertInstanceOf(Identity::class, $identity);
        self::assertInstanceOf(Customer::class, $customer);
        self::assertInstanceOf(Cart::class, $cart);
        self::assertCount(1, ShopperStory::getPool('identities'));
        self::assertSame(CustomerId::forIdentity($identity->id->toString())->toString(), $repositories->get(Customer::class)->load($customer->aggregateRootId())->aggregateRootId()->toString());
        self::assertInstanceOf(Cart::class, $repositories->get(Cart::class)->load($cart->aggregateRootId()));
    }

    #[Test]
    public function itRunsLoadFixturesCommandAppending(): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('foundry:load-fixtures'));

        $status = $tester->execute(['name' => ['shoppers'], '--append' => true], ['interactive' => false]);

        self::assertSame(0, $status, $tester->getDisplay());
    }
}
