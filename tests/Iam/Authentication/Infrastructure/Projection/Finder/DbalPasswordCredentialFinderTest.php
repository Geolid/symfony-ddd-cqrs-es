<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalPasswordCredentialFinderTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;
    private StubPasswordStrengthSpecification $passwordStrength;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
        $this->passwordStrength = new StubPasswordStrengthSpecification();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itFindsByIdentity(): void
    {
        // Given
        $other = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $password = PasswordFactory::new()->create()->value;

        $credential = PasswordCredentialFactory::new()
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentityOrNull($credential->identityId);
        $nothing = $this->finder->ofIdentityOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSame(
            $credential->definedAt->format(\DateTimeInterface::ATOM),
            $result->definedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $credential->definedAt->format(\DateTimeInterface::ATOM),
            $result->changedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame($this->hasher->hash($password), $result->passwordHash);

        self::assertNull($nothing);
    }
}
