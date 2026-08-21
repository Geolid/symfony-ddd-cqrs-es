<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalApiKeyCredentialProjector;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, revoked: bool, identity_authenticatable: bool}
 */
final class DbalApiKeyCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private IdentityRepositoryInterface $identityRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityRepository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialIssued(): void
    {
        // When
        $credential = ApiKeyCredentialTestFactory::new()
            ->withLabel('CI pipeline')
            ->withHasher(new StubApiKeyHasher())
            ->store();

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame('CI pipeline', $row['label']);
        self::assertFalse((bool) $row['revoked']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnApiKeyCredentialRevoked(): void
    {
        // Given
        $other = ApiKeyCredentialTestFactory::new()->withHasher(new StubApiKeyHasher())->store();

        // When
        $credential = ApiKeyCredentialTestFactory::new()->withHasher(new StubApiKeyHasher())->revoked()->store();

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['revoked']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['revoked']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = ApiKeyCredentialTestFactory::new()->withHasher(new StubApiKeyHasher())->store();
        $identity = IdentityTestFactory::new()->store();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
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
        $identity = IdentityTestFactory::new()->suspended()->store();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher(new StubApiKeyHasher())
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT label, revoked, identity_authenticatable FROM %s WHERE id = :id', DbalApiKeyCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
