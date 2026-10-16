<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ErasePendingIdentity;

use Iam\Identity\Application\Command\ErasePendingIdentity\ErasePendingIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ErasePendingIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    #[DataProvider('provideExpiry')]
    public function itErases(string $expiry, string $registeredAtOffset, bool $retained): void
    {
        // Given
        $identity = IdentityFactory::new()
            ->withExpiry($expiry)
            ->withRegisteredAt(Clock::get()->now()->modify($registeredAtOffset))
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofEmailOrNull($identity->email->value);
        self::assertSame($retained, null !== $result);
    }

    #[Test]
    public function itIgnoresWhenConfirmed(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '24 hours';
        $identity = IdentityFactory::new()
            ->withExpiry($expiry)
            ->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))
            ->confirmed()
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($identity->id->toString(), $result->id);
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '24 hours';
        $identity = IdentityFactory::new()
            ->withExpiry($expiry)
            ->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))
            ->erasureRequested()
            ->erased()
            ->create();
        $this->store($identity);

        // When
        $this->dispatch(new ErasePendingIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = IdentityIdFactory::new()->create()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ErasePendingIdentity($unknownId));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideExpiry(): iterable
    {
        yield 'before expiry' => ['24 hours', '-24 hours +1 second', true];
        yield 'after expiry' => ['24 hours', '-24 hours -1 second', false];
    }
}
