<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\Validation\ValidUniqueLogin;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Tests\Support\Doubles\FakeUniqueValueRegistry;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueLogin>
 */
final class ValidUniqueLoginTest extends CompoundConstraintTestCase
{
    private FakeUniqueValueRegistry $registry;

    protected function setUp(): void
    {
        // Before parent::setUp() — it calls createValidator(), which reads $this->registry.
        $this->registry = new FakeUniqueValueRegistry();

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
        $this->registry->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'john.doe', 'owner-id');

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
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                UniqueValueValidator::class => new UniqueValueValidator($this->registry),
            ]))
            ->getValidator();
    }
}
