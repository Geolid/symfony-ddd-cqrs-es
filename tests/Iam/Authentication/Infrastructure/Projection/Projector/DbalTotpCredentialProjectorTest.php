<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalTotpCredentialProjector;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{enrolled_at: string, unenrolled: bool, unenrolled_at: string|null}
 */
final class DbalTotpCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private FakeTotpCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = new FakeTotpCipher();
    }

    #[Test]
    public function itProjectsOnTotpCredentialEnrolled(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['enrolledAt']->format(self::DATE_FORMAT), $row['enrolled_at']);
        self::assertFalse((bool) $row['unenrolled']);
        self::assertNull($row['unenrolled_at']);
    }

    #[Test]
    public function itProjectsOnTotpCredentialUnenrolled(): void
    {
        // Given
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();
        $this->store($other);

        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->unenrolled();
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['unenrolled']);
        self::assertSame($builder['unenrolledAt']->format(self::DATE_FORMAT), $row['unenrolled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['unenrolled']);
        self::assertNull($otherRow['unenrolled_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $credential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->cipher)
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
            \sprintf('SELECT enrolled_at, unenrolled, unenrolled_at FROM %s WHERE id = :id', DbalTotpCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
