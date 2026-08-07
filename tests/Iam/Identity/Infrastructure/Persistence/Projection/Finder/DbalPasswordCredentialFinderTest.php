<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Finder;

use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
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
    public function itFindsAPasswordCredentialByLogin(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()->withLogin('buyer@example.com')->create();
        $this->store($credential);

        // When
        $result = $this->finder->ofLogin('buyer@example.com');

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->login);
    }

    #[Test]
    public function itFindsNoCredentialForAnUnknownLogin(): void
    {
        // When
        $result = $this->finder->ofLogin('unknown@example.com');

        // Then
        self::assertNull($result);
    }
}
