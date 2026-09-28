<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalDeviceTrustProjector;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{identity_id: string, revoked_at: string}
 */
final class DbalDeviceTrustProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnDeviceTrustRevoked(): void
    {
        // Given
        $otherBuilder = DeviceTrustBuilder::new();
        $other = $otherBuilder->create();

        $builder = DeviceTrustBuilder::new();
        $deviceTrust = $builder->create();

        // When
        $this->store($other, $deviceTrust);

        // Then
        $row = $this->fetchRow($builder['identityId']);
        self::assertNotFalse($row);
        self::assertSame($builder['revokedAt']->format(self::DATE_FORMAT), $row['revoked_at']);

        $otherRow = $this->fetchRow($otherBuilder['identityId']);
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnDeviceTrustRevokedAgain(): void
    {
        // Given
        $otherBuilder = DeviceTrustBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);

        $builder = DeviceTrustBuilder::new();
        $deviceTrust = $builder->create();
        $this->store($deviceTrust);

        $revokedAgainAt = DeviceTrustBuilder::sample('revokedAgainAt');

        // When
        $deviceTrust->revoke($builder['identityId'], $revokedAgainAt);
        $this->store($deviceTrust);

        // Then
        $row = $this->fetchRow($builder['identityId']);
        self::assertNotFalse($row);
        self::assertSame($revokedAgainAt->format(self::DATE_FORMAT), $row['revoked_at']);

        $otherRow = $this->fetchRow($otherBuilder['identityId']);
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['revokedAt']->format(self::DATE_FORMAT), $otherRow['revoked_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $otherBuilder = DeviceTrustBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $deviceTrust = DeviceTrustBuilder::new()->withIdentityId($identity->id->toString())->create();

        // When
        $this->store($deviceTrust, $identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($otherBuilder['identityId']));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $identityId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT identity_id, revoked_at FROM %s WHERE identity_id = :identityId', DbalDeviceTrustProjector::TABLE),
            ['identityId' => $identityId],
        );
    }
}
