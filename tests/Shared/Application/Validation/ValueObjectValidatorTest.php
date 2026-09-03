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
    public function itAccepts(mixed $value, ValidValueObject $constraint): void
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
        yield 'scalar value' => ['acceptable', self::validValueObject()];
        yield 'spread array' => [['left', 'right'], new ValidValueObject(StubValueObject::class, method: 'of')];
    }

    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $value, ValidValueObject $constraint, string $reason): void
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
    public static function provideRefusedValues(): iterable
    {
        yield 'refused' => ['refused', self::validValueObject(), 'Refused by the value object.'];
        yield 'mistyped' => ['mistyped', self::validValueObject(), 'Expected a different type.'];
        yield 'out of range' => ['out-of-range', self::validValueObject(), 'Outside the accepted range.'];
        yield 'spread array' => [['same', 'same'], new ValidValueObject(StubValueObject::class, method: 'of'), 'Refused a matching pair.'];
    }

    #[Test]
    #[DataProvider('provideEmptyValues')]
    public function itAcceptsEmptyValue(mixed $value): void
    {
        // When
        $this->validator->validate($value, self::validValueObject());

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itSkipsWhenPathAlreadyViolated(): void
    {
        // Given
        $this->context->buildViolation('Already invalid.')->addViolation();

        // When
        $this->validator->validate('refused', self::validValueObject());

        // Then
        $violations = $this->context->getViolations();

        self::assertCount(1, $violations);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideEmptyValues(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'empty list' => [[]];
    }

    #[Test]
    public function itFailsWhenConstraintNotSupported(): void
    {
        // Given
        $constraint = new NotBlank();

        // Then
        $this->expectException(UnexpectedTypeException::class);

        // When
        $this->validator->validate('acceptable', $constraint);
    }

    protected function createValidator(): ValueObjectValidator
    {
        return new ValueObjectValidator();
    }

    private static function validValueObject(): ValidValueObject
    {
        return new ValidValueObject(StubValueObject::class, method: 'fromString');
    }
}

final readonly class StubValueObject
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

    public static function of(string $a, string $b): self
    {
        if ($a === $b) {
            throw new \InvalidArgumentException('Refused a matching pair.');
        }

        return new self();
    }
}
