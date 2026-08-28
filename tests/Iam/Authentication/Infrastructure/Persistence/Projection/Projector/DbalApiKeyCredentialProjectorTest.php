<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalApiKeyCredentialProjector;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{label: string, issued_at: string, revoked: bool, revoked_at: string|null, identity_authenticatable: bool}
 */
final class DbalApiKeyCredentialProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnApiKeyCredentialIssued(): void
    {
        // Given
        $credential = ApiKeyCredentialTestFactory::new()
            ->withLabel('CI pipeline')
            ->withIssuedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withHasher(new StubApiKeyHasher())
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame('CI pipeline', $row['label']);
        self::assertSame('2026-01-01 00:00:00', $row['issued_at']);
        self::assertFalse((bool) $row['revoked']);
        self::assertNull($row['revoked_at']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialRevoked(): void
    {
        // Given
        $other = $this->otherCredential();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withHasher(new StubApiKeyHasher())
            ->revoked(revokedAt: new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['revoked']);
        self::assertSame('2026-01-02 00:00:00', $row['revoked_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['revoked']);
        self::assertNull($otherRow['revoked_at']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = $this->otherCredential();
        $identity = IdentityTestFactory::new()->create();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->create();
        $this->store($identity, $credential);

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), Clock::get()->now());
        $this->store($identity);

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
        $other = $this->otherCredential(suspended: true);
        $identity = IdentityTestFactory::new()->suspended()->create();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->create();
        $this->store($credential, $identity);

        // When
        $identity->reactivate(Reason::fromString('Appeal upheld'), Clock::get()->now());
        $this->store($identity);

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
        $other = $this->otherCredential();
        $identity = IdentityTestFactory::new()->create();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->create();
        $this->store($identity, $credential);

        // When
        $identity->erase(Clock::get()->now());
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    private function otherCredential(bool $suspended = false): ApiKeyCredential
    {
        $identity = IdentityTestFactory::new()->create();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->create();
        $this->store($identity, $credential);

        if ($suspended) {
            $identity->suspend(Reason::fromString('Suspected fraudulent activity'), Clock::get()->now());
            $this->store($identity);
        }

        return $credential;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT label, issued_at, revoked, revoked_at, identity_authenticatable FROM %s WHERE id = :id', DbalApiKeyCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
