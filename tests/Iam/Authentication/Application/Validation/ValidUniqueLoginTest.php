<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\PasswordCredentialUniqueKey;
use Iam\Authentication\Application\Validation\ValidUniqueLogin;
use Iam\Tests\Authentication\Support\ValidatorFactoryTrait;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Tests\Support\Double\FakeUniquenessRegistry;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueLogin>
 */
final class ValidUniqueLoginTest extends CompoundConstraintTestCase
{
    use ValidatorFactoryTrait;

    private FakeUniquenessRegistry $uniqueness;

    protected function setUp(): void
    {
        // Before parent::setUp() — it calls createValidator(), which reads $this->uniqueness.
        $this->uniqueness = new FakeUniquenessRegistry();

        parent::setUp();
    }

    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('john.doe');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $this->uniqueness->claim(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'john.doe', 'owner-id');

        // When
        $this->validateValue('john.doe');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(PasswordCredentialUniqueKey::LOGIN)]);
    }

    protected function createCompound(): ValidUniqueLogin
    {
        return new ValidUniqueLogin();
    }

    protected function createValidator(): ValidatorInterface
    {
        return $this->validatorUsing(UniqueValueValidator::class, new UniqueValueValidator($this->uniqueness));
    }
}
