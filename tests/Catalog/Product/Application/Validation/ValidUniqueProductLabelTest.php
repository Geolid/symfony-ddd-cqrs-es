<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Validation;

use Catalog\Product\Application\Validation\ValidUniqueProductLabel;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
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
 * @extends CompoundConstraintTestCase<ValidUniqueProductLabel>
 */
final class ValidUniqueProductLabelTest extends CompoundConstraintTestCase
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
        $this->validateValue('mug');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefusesALabelAlreadyReserved(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(ProductUniqueKey::LABEL), 'mug', 'owner-id');

        // When
        $this->validateValue('mug');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(ProductUniqueKey::LABEL)]);
    }

    protected function createCompound(): ValidUniqueProductLabel
    {
        return new ValidUniqueProductLabel();
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
