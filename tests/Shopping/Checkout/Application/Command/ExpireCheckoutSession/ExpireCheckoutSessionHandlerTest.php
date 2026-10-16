<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\ExpireCheckoutSession;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\ExpireCheckoutSession\ExpireCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ExpireCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    #[DataProvider('provideExpiry')]
    public function itExpires(string $expiry, string $openedAtOffset, CheckoutSessionStatus $expectedStatus): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt(Clock::get()->now()->modify($openedAtOffset))
            ->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        $result = $this->finder->ofId($checkoutSession->id->toString());
        self::assertSame($expectedStatus, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyExpired(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->expired()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new ExpireCheckoutSession($checkoutSession->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = CheckoutSessionIdFactory::new()->create()->toString();

        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new ExpireCheckoutSession($unknownId));
    }

    /**
     * @return iterable<string, array{string, string, CheckoutSessionStatus}>
     */
    public static function provideExpiry(): iterable
    {
        yield 'before expiry' => ['30 minutes', '-30 minutes +1 second', CheckoutSessionStatus::OPEN];
        yield 'after expiry' => ['30 minutes', '-30 minutes -1 second', CheckoutSessionStatus::EXPIRED];
    }
}
