<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{password_hash: string, defined_at: string, changed_at: string}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private PasswordStrengthSpecificationInterface $passwordStrength;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new StubPasswordStrengthSpecification();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itProjectsOnPasswordCredentialDefined(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['changed_at']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialChanged(): void
    {
        // Given
        $other = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $newPassword = 'updated-password';
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->changed($newPassword, $this->passwordStrength, $this->hasher)->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($newPassword), $row['password_hash']);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($credential->changedAt?->format(self::DATE_FORMAT), $row['changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotSame($this->hasher->hash($newPassword), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialRehashed(): void
    {
        // Given
        $otherPassword = PasswordFactory::new()->create()->value;
        $other = PasswordCredentialFactory::new()->withPassword($otherPassword)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)->create();
        $this->store($other);
        $password = PasswordFactory::new()->create()->value;

        $credential = PasswordCredentialFactory::new()->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->rehashed($password, $this->hasher)
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($password), $row['password_hash']);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($this->hasher->hash($otherPassword), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialReset(): void
    {
        // Given
        $other = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $newPassword = 'updated-password';
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->reset($newPassword, $this->passwordStrength, $this->hasher)->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($newPassword), $row['password_hash']);
        self::assertSame($credential->definedAt->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($credential->resetAt?->format(self::DATE_FORMAT), $row['changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotSame($this->hasher->hash($newPassword), $otherRow['password_hash']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $credential = PasswordCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
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
            \sprintf('SELECT password_hash, defined_at, changed_at FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
