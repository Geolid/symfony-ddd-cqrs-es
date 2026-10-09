<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalApiKeyCredentialProjector;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, issued_at: string, revoked: bool, revoked_at: string|null}
 */
final class DbalApiKeyCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private FakeApiKeyHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new FakeApiKeyHasher();
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialIssued(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($credential->label->value, $row['label']);
        self::assertSame($credential->issuedAt->format(self::DATE_FORMAT), $row['issued_at']);
        self::assertFalse((bool) $row['revoked']);
        self::assertNull($row['revoked_at']);
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialRevoked(): void
    {
        // Given
        $other = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();
        $this->store($other);

        $credential = ApiKeyCredentialFactory::new()
            ->withHasher($this->hasher)
            ->revoked()->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['revoked']);
        self::assertSame($credential->revokedAt?->format(self::DATE_FORMAT), $row['revoked_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['revoked']);
        self::assertNull($otherRow['revoked_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();
        $this->store($other);

        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $credential = ApiKeyCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($credential, $identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT label, issued_at, revoked, revoked_at FROM %s WHERE id = :id', DbalApiKeyCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
