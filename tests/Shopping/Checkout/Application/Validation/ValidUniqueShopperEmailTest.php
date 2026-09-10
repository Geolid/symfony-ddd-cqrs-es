<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Validation;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Tests\Support\Double\FakeUniquenessRegistry;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Shopping\Checkout\Application\Validation\ValidUniqueShopperEmail;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueShopperEmail>
 */
final class ValidUniqueShopperEmailTest extends CompoundConstraintTestCase
{
    private FakeUniquenessRegistry $registry;

    protected function setUp(): void
    {
        // Before parent::setUp() — it calls createValidator(), which reads $this->registry.
        $this->registry = new FakeUniquenessRegistry();

        parent::setUp();
    }

    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('shopper@example.com');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $this->registry->claim(UniqueKey::for(ShopperUniqueKey::EMAIL), 'shopper@example.com', 'owner-id');

        // When
        $this->validateValue('shopper@example.com');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(ShopperUniqueKey::EMAIL)]);
    }

    protected function createCompound(): ValidUniqueShopperEmail
    {
        return new ValidUniqueShopperEmail();
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
