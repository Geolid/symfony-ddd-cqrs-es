<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Shared\Application\Validation\ValueObjectValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ValueObjectValidatorTest extends ConstraintValidatorTestCase
{
    #[Test]
    public function itAcceptsAValueItsValueObjectAccepts(): void
    {
        // When
        $this->validator->validate('acceptable', new ValidValueObject(DummyValue::class));

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusals')]
    public function itReportsWhyTheValueObjectRefusedTheValue(string $value, string $reason): void
    {
        // When
        $this->validator->validate($value, new ValidValueObject(DummyValue::class));

        // Then
        $this->buildViolation('Domain validation failed: {{ reason }}')
            ->setParameter('{{ reason }}', $reason)
            ->setCode(ValidValueObject::DOMAIN_VALIDATION_ERROR)
            ->assertRaised();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRefusals(): iterable
    {
        yield 'a value the invariants reject' => ['refused', 'Refused by the value object.'];
        yield 'a value of the wrong type' => ['mistyped', 'Expected a different type.'];
        yield 'a value outside the accepted range' => ['out-of-range', 'Outside the accepted range.'];
    }

    #[Test]
    #[DataProvider('provideEmptyValues')]
    public function itLeavesAnEmptyValueToTheConstraintThatOwnsIt(mixed $value): void
    {
        // When
        $this->validator->validate($value, new ValidValueObject(DummyValue::class));

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideEmptyValues(): iterable
    {
        yield 'nothing' => [null];
        yield 'an empty string' => [''];
        yield 'an empty list' => [[]];
    }

    #[Test]
    public function itFailsOnAConstraintItDoesNotValidate(): void
    {
        // Then
        $this->expectException(UnexpectedTypeException::class);

        // When
        $this->validator->validate('acceptable', new NotBlank());
    }

    protected function createValidator(): ValueObjectValidator
    {
        return new ValueObjectValidator();
    }
}

final readonly class DummyValue
{
    private function __construct()
    {
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'refused' => throw new \InvalidArgumentException('Refused by the value object.'),
            'mistyped' => throw new \TypeError('Expected a different type.'),
            'out-of-range' => throw new \ValueError('Outside the accepted range.'),
            default => new self(),
        };
    }
}
