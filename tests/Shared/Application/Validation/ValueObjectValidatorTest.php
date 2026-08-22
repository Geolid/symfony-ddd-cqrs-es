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

/**
 * @extends ConstraintValidatorTestCase<ValueObjectValidator>
 */
final class ValueObjectValidatorTest extends ConstraintValidatorTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAcceptsWhenTheValueObjectConstructsSuccessfully(mixed $value, ValidValueObject $constraint): void
    {
        // When
        $this->validator->validate($value, $constraint);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{mixed, ValidValueObject}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'a scalar value' => ['acceptable', new ValidValueObject(StubValue::class, method: 'fromString')];
        yield 'an array spread into the constructor' => [['left', 'right'], new ValidValueObject(StubPair::class, method: 'of')];
    }

    #[Test]
    #[DataProvider('provideRefusals')]
    public function itReportsWhyTheValueObjectRefusedTheValue(mixed $value, ValidValueObject $constraint, string $reason): void
    {
        // When
        $this->validator->validate($value, $constraint);

        // Then
        $this->buildViolation('Domain validation failed: {{ reason }}')
            ->setParameter('{{ reason }}', $reason)
            ->setCode(ValidValueObject::DOMAIN_VALIDATION_ERROR)
            ->assertRaised();
    }

    /**
     * @return iterable<string, array{mixed, ValidValueObject, string}>
     */
    public static function provideRefusals(): iterable
    {
        yield 'a value the invariants reject' => ['refused', new ValidValueObject(StubValue::class, method: 'fromString'), 'Refused by the value object.'];
        yield 'a value of the wrong type' => ['mistyped', new ValidValueObject(StubValue::class, method: 'fromString'), 'Expected a different type.'];
        yield 'a value outside the accepted range' => ['out-of-range', new ValidValueObject(StubValue::class, method: 'fromString'), 'Outside the accepted range.'];
        yield 'a spread array value' => [['same', 'same'], new ValidValueObject(StubPair::class, method: 'of'), 'Refused a matching pair.'];
    }

    #[Test]
    #[DataProvider('provideEmptyValues')]
    public function itLeavesAnEmptyValueToTheConstraintThatOwnsIt(mixed $value): void
    {
        // When
        $this->validator->validate($value, new ValidValueObject(StubValue::class, method: 'fromString'));

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
    public function itCarriesTheGroupsAndPayloadItWasGiven(): void
    {
        // When
        $constraint = new ValidValueObject(StubValue::class, method: 'fromString', groups: ['registration'], payload: 'severity');

        // Then
        self::assertSame(['registration'], $constraint->groups);
        self::assertSame('severity', $constraint->payload);
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

final readonly class StubValue
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

final readonly class StubPair
{
    private function __construct()
    {
    }

    public static function of(string $a, string $b): self
    {
        if ($a === $b) {
            throw new \InvalidArgumentException('Refused a matching pair.');
        }

        return new self();
    }
}
