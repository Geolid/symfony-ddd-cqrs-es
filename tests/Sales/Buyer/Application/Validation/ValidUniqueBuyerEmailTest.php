<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Validation;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\Validation\ValidUniqueBuyerEmail;
use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Tests\Support\Double\FakeUniqueValueRegistry;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueBuyerEmail>
 */
final class ValidUniqueBuyerEmailTest extends CompoundConstraintTestCase
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
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(BuyerUniqueKey::EMAIL), 'buyer@example.com', 'owner-id');

        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(BuyerUniqueKey::EMAIL)]);
    }

    protected function createCompound(): ValidUniqueBuyerEmail
    {
        return new ValidUniqueBuyerEmail();
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
