<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Identity\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{id: string, status: string, reason: string|null, registered_at: string, suspended_at: string|null, reactivated_at: string|null}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnIdentityRegistered(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityTestFactory::new()->withRegisteredAt($now)->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertNull($row['reason']);
        self::assertSame($now->format('Y-m-d H:i:s'), $row['registered_at']);
        self::assertNull($row['suspended_at']);
        self::assertNull($row['reactivated_at']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspended(): void
    {
        // Given
        $now = Clock::get()->now();
        $suspendedAt = $now->modify('+1 day');
        $other = IdentityTestFactory::new()->create();
        $identity = IdentityTestFactory::new()->create();
        $this->store($other, $identity);

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), $suspendedAt);
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['status']);
        self::assertSame('Suspected fraudulent activity', $row['reason']);
        self::assertSame($suspendedAt->format('Y-m-d H:i:s'), $row['suspended_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::ACTIVE->value, $otherRow['status']);
        self::assertNull($otherRow['reason']);
        self::assertNull($otherRow['suspended_at']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivated(): void
    {
        // Given
        $now = Clock::get()->now();
        $reactivatedAt = $now->modify('+1 day');
        $otherFactory = IdentityTestFactory::new()->suspended();
        $other = $otherFactory->create();
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($other, $identity);

        // When
        $identity->reactivate(Reason::fromString('Appeal upheld'), $reactivatedAt);
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertSame('Appeal upheld', $row['reason']);
        self::assertSame($reactivatedAt->format('Y-m-d H:i:s'), $row['reactivated_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['status']);
        self::assertSame($otherFactory->attribute('reason'), $otherRow['reason']);
        self::assertNull($otherRow['reactivated_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErased(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $identity = IdentityTestFactory::new()->create();
        $this->store($other, $identity);

        // When
        $identity->erase(Clock::get()->now());
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT id, status, reason, registered_at, suspended_at, reactivated_at FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );
    }
}
