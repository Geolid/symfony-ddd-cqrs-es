<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalAuthenticatableIdentityProjector;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{authenticatable: bool}
 */
final class DbalAuthenticatableIdentityProjectorTest extends AbstractIntegrationTestCase
{
    private IdentityRepositoryInterface $identityRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityRepository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itProjectsOnIdentityRegisteredIntegrationEvent(): void
    {
        // When
        $identity = IdentityTestFactory::new()->store();

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['authenticatable']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $identity = IdentityTestFactory::new()->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['authenticatable']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['authenticatable']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivatedIntegrationEvent(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['authenticatable']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $identityId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT authenticatable FROM %s WHERE identity_id = :identityId', DbalAuthenticatableIdentityProjector::TABLE),
            ['identityId' => $identityId],
        );
    }
}
