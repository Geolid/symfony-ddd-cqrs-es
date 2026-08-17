<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Validation;

use Iam\Identity\Application\Validation\ValidUniqueLogin;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueKey;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Domain\ValueObject\UniqueKey;
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
        $this->registry = new FakeUniqueValueRegistry();

        parent::setUp();
    }

    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('operator');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefusesALoginAlreadyReserved(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'operator', 'owner-id');

        // When
        $this->validateValue('operator');

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
