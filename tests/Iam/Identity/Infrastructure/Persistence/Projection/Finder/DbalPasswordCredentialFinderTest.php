<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Finder;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalPasswordCredentialFinderTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itGetsAPasswordCredentialByLogin(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()->withLogin('buyer@example.com')->withHasher(new DummySecretHasher())->create();
        $this->store($credential);

        // When
        $result = $this->finder->ofLogin('buyer@example.com');

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->login);
    }

    #[Test]
    public function itThrowsOnAnUnknownLogin(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofLogin('unknown@example.com');
    }
}
