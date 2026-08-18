<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Validation\ValidUniqueCustomerEmail;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Domain\ValueObject\UniqueKey;
use Shared\Tests\Support\Doubles\FakeUniqueValueRegistry;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueCustomerEmail>
 */
final class ValidUniqueCustomerEmailTest extends CompoundConstraintTestCase
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
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefusesWhenAlreadyReserved(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), 'buyer@example.com', 'owner-id');

        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(CustomerUniqueKey::EMAIL)]);
    }

    protected function createCompound(): ValidUniqueCustomerEmail
    {
        return new ValidUniqueCustomerEmail();
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
