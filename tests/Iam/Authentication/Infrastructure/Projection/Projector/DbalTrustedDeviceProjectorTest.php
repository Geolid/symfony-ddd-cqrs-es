<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalTrustedDeviceProjector;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{version: int, user_agent: string, ip: string, trusted_at: string, revoked_at: string|null}
 */
final class DbalTrustedDeviceProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnTrustedDeviceTrusted(): void
    {
        // Given
        $builder = TrustedDeviceBuilder::new();
        $trustedDevice = $builder->create();

        // When
        $this->store($trustedDevice);

        // Then
        $row = $this->fetchRow($trustedDevice->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['version'], $row['version']);
        self::assertSame($builder['userAgent'], $row['user_agent']);
        self::assertSame($builder['ip'], $row['ip']);
        self::assertSame($builder['trustedAt']->format(self::DATE_FORMAT), $row['trusted_at']);
        self::assertNull($row['revoked_at']);
    }

    #[Test]
    public function itProjectsOnTrustedDeviceRevoked(): void
    {
        // Given
        $other = TrustedDeviceBuilder::new()->create();

        $builder = TrustedDeviceBuilder::new()->revoked();
        $trustedDevice = $builder->create();

        // When
        $this->store($other, $trustedDevice);

        // Then
        $row = $this->fetchRow($trustedDevice->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['revokedAt']->format(self::DATE_FORMAT), $row['revoked_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['revoked_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = TrustedDeviceBuilder::new()->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $trustedDevice = TrustedDeviceBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->create();

        // When
        $this->store($trustedDevice, $identity);

        // Then
        self::assertFalse($this->fetchRow($trustedDevice->id->toString()));
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
            \sprintf('SELECT version, user_agent, ip, trusted_at, revoked_at FROM %s WHERE id = :id', DbalTrustedDeviceProjector::TABLE),
            ['id' => $id],
        );
    }
}
