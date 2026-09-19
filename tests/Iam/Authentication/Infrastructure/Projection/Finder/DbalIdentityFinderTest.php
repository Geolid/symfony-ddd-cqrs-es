<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\IdentityModerationStatus;
use Iam\Authentication\Application\IdentityVerificationStatus;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\Faker\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalIdentityFinderTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder->ofId($identity->id->toString());

        // Then
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame($builder['fullName']->value, $result->fullName);
        self::assertSame($builder['email']->value, $result->email);
        self::assertSame(IdentityVerificationStatus::CONFIRMED, $result->verificationStatus);
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itGetsByEmail(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder->ofEmail($builder['email']->value);

        // Then
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame($builder['fullName']->value, $result->fullName);
        self::assertSame($builder['email']->value, $result->email);
        self::assertSame(IdentityVerificationStatus::CONFIRMED, $result->verificationStatus);
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
    }

    #[Test]
    public function itThrowsWhenEmailNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder->ofEmail(SeededFaker::get()->unique()->safeEmail());
    }
}
