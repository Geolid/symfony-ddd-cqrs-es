<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrength;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{login: string, password_hash: string, defined_at: string, password_changed_at: string, identity_authenticatable: bool}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private PasswordStrengthInterface $passwordStrength;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new StubPasswordStrength();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itProjectsOnPasswordCredentialDefined(): void
    {
        // Given
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['login']->value, $row['login']);
        self::assertSame($builder['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($builder['definedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialChanged(): void
    {
        // Given
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $newPassword = 'updated-password';
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->changed($newPassword, $this->passwordStrength, $this->hasher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($newPassword), $row['password_hash']);
        self::assertSame($builder['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($builder['changedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotSame($this->hasher->hash($newPassword), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialRehashed(): void
    {
        // Given
        $otherBuilder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $other = $otherBuilder->create();
        $this->store($other);

        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $builder = $builder->rehashed($builder['password']->value, $this->hasher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($builder['password']->value), $row['password_hash']);
        self::assertSame($builder['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($builder['definedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($this->hasher->hash($otherBuilder['password']->value), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->suspended()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
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
        $other = PasswordCredentialBuilder::new()
            ->withIdentityId($otherIdentity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $identity = IdentityBuilder::new()->suspended()->reactivated()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
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
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erased()->create();
        $credential = PasswordCredentialBuilder::new()
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
            \sprintf('SELECT login, password_hash, defined_at, password_changed_at, identity_authenticatable FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
