<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Tests\Support\Double\DummyUniqueKey;
use Shared\Tests\Support\Double\FakeUniquenessRegistry;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UniqueValueValidator>
 */
final class UniqueValueValidatorTest extends ConstraintValidatorTestCase
{
    private const string SUBJECT_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    private FakeUniquenessRegistry $uniqueness;
    private ValidUniqueValue $baseConstraint;
    private ValidUniqueValue $exclusionConstraint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseConstraint = new ValidUniqueValue(DummyUniqueKey::NAME);
        $this->exclusionConstraint = new ValidUniqueValue(DummyUniqueKey::NAME, excludeSubjectIdPropertyPath: 'id');
    }

    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validator->validate('reserved-value', $this->baseConstraint);

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $this->uniqueness->claim(UniqueKey::for(DummyUniqueKey::NAME), 'reserved-value', self::SUBJECT_ID);

        // When
        $this->validator->validate('reserved-value', $this->baseConstraint);

        // Then
        $this->assertViolationRaised();
    }

    #[Test]
    public function itRefusesWithScope(): void
    {
        // Given
        $scope = 'scope-a';
        $this->uniqueness->claim(UniqueKey::for(DummyUniqueKey::NAME, $scope), 'reserved-value', self::SUBJECT_ID);

        // When
        $this->validator->validate('reserved-value', new ValidUniqueValue(DummyUniqueKey::NAME, [$scope]));

        // Then
        $this->assertViolationRaised();
    }

    #[Test]
    public function itAcceptsWhenSubjectMatches(): void
    {
        // Given
        $this->uniqueness->claim(UniqueKey::for(DummyUniqueKey::NAME), 'reserved-value', self::SUBJECT_ID);
        $this->setObject(new DummyEditedObject(self::SUBJECT_ID));

        // When
        $this->validator->validate('reserved-value', $this->exclusionConstraint);

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefusesWhenSubjectDiffers(): void
    {
        // Given
        $this->uniqueness->claim(UniqueKey::for(DummyUniqueKey::NAME), 'reserved-value', '0199a1b2-3c4d-7e5f-8061-72839405a6b8');
        $this->setObject(new DummyEditedObject(self::SUBJECT_ID));

        // When
        $this->validator->validate('reserved-value', $this->exclusionConstraint);

        // Then
        $this->assertViolationRaised();
    }

    #[Test]
    public function itFailsWhenObjectMissing(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->validator->validate('reserved-value', $this->exclusionConstraint);
    }

    #[Test]
    public function itFailsWhenSubjectIdInvalid(): void
    {
        // Given
        $this->setObject(new DummyEditedObject(42));

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->validator->validate('reserved-value', $this->exclusionConstraint);
    }

    #[Test]
    #[DataProvider('provideEmptyValues')]
    public function itAcceptsEmptyValue(mixed $value): void
    {
        // When
        $this->validator->validate($value, $this->baseConstraint);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideEmptyValues(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    #[Test]
    public function itFailsWhenConstraintNotSupported(): void
    {
        // Given
        $constraint = new NotBlank();

        // Then
        $this->expectException(UnexpectedTypeException::class);

        // When
        $this->validator->validate('reserved-value', $constraint);
    }

    #[Test]
    public function itFailsWhenValueNotString(): void
    {
        // Then
        $this->expectException(UnexpectedValueException::class);

        // When
        $this->validator->validate(42, $this->baseConstraint);
    }

    protected function createValidator(): UniqueValueValidator
    {
        $this->uniqueness = new FakeUniquenessRegistry();

        return new UniqueValueValidator($this->uniqueness);
    }

    private function assertViolationRaised(): void
    {
        $this->buildViolation('Value "{{ value }}" is already in use for {{ key }}.')
            ->setParameter('{{ value }}', 'reserved-value')
            ->setParameter('{{ key }}', 'NAME')
            ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
            ->assertRaised();
    }
}

final readonly class DummyEditedObject
{
    public function __construct(public int|string $id)
    {
    }
}
