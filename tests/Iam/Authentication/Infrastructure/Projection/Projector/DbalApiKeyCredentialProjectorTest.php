<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalApiKeyCredentialProjector;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, issued_at: string, revoked: bool, revoked_at: string|null, identity_authenticatable: bool}
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
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['label']->value, $row['label']);
        self::assertSame($builder['issuedAt']->format(self::DATE_FORMAT), $row['issued_at']);
        self::assertFalse((bool) $row['revoked']);
        self::assertNull($row['revoked_at']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialRevoked(): void
    {
        // Given
        $other = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->create();
        $this->store($other);

        $builder = ApiKeyCredentialBuilder::new()
            ->withHasher($this->hasher)
            ->revoked();
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['revoked']);
        self::assertSame($builder['revokedAt']->format(self::DATE_FORMAT), $row['revoked_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['revoked']);
        self::assertNull($otherRow['revoked_at']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->suspended()->create();
        $credential = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($credential, $identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['identity_authenticatable']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivatedIntegrationEvent(): void
    {
        // Given
        $otherIdentity = IdentityBuilder::new()->suspended()->create();
        $other = ApiKeyCredentialBuilder::new()
            ->withIdentityId($otherIdentity->id->toString())
            ->withHasher($this->hasher)
            ->create();

        $identity = IdentityBuilder::new()->suspended()->reactivated()->create();
        $credential = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($other, $otherIdentity, $credential, $identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['identity_authenticatable']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['identity_authenticatable']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erased()->create();
        $credential = ApiKeyCredentialBuilder::new()
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
            \sprintf('SELECT label, issued_at, revoked, revoked_at, identity_authenticatable FROM %s WHERE id = :id', DbalApiKeyCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
