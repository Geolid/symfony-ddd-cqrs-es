<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use PHPUnit\Framework\Attributes\Test;
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
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentityOrNull($builder['identityId']);
        $nothing = $this->finder->ofIdentityOrNull(PasswordCredentialBuilder::sample('identityId'));

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame(
            $builder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->definedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $builder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->changedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame($this->hasher->hash($builder['password']->value), $result->passwordHash);

        self::assertNull($nothing);
    }
}
