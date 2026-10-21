<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Symfony\Component\Clock\Clock;

final class SweepCheckoutSessionsDueForExpiryCommandTest extends AbstractCronTestCase
{
    private CheckoutSessionFinderInterface $checkoutSessionFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkoutSessionFinder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itExpires(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '30 minutes';
        $fresh = CheckoutSessionFactory::new()->withExpiry($expiry)->withOpenedAt($now->modify(\sprintf('-%s +1 second', $expiry)))->create();
        $due = CheckoutSessionFactory::new()->withExpiry($expiry)->withOpenedAt($now->modify(\sprintf('-%s -1 second', $expiry)))->create();
        $this->store($fresh, $due);

        // When
        $this->tester()->run(['command' => 'shopping:checkout:sweep-due-for-expiry']);

        // Then
        $freshResult = $this->checkoutSessionFinder->ofId($fresh->id->toString());
        $dueResult = $this->checkoutSessionFinder->ofId($due->id->toString());
        self::assertSame(CheckoutSessionStatus::OPEN, $freshResult->status);
        self::assertSame(CheckoutSessionStatus::EXPIRED, $dueResult->status);
    }
}
