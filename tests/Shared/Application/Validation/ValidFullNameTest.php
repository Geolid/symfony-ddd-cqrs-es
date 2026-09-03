<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidFullName;
use Shared\Domain\ValueObject\FullName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidFullName>
 */
final class ValidFullNameTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(self::fullName());

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param array{firstName: string, lastName: string} $value
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(array $value): void
    {
        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    /**
     * @return iterable<string, array{array{firstName: string, lastName: string}}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty first name' => [self::fullName(['firstName' => ''])];
        yield 'whitespace only first name' => [self::fullName(['firstName' => '   '])];
        yield 'empty last name' => [self::fullName(['lastName' => ''])];
        yield 'first name too long' => [self::fullName(['firstName' => str_repeat('a', FullName::MAX_LENGTH + 1)])];
        yield 'last name too long' => [self::fullName(['lastName' => str_repeat('a', FullName::MAX_LENGTH + 1)])];
    }

    #[Test]
    public function itRefusesMultipleFields(): void
    {
        // Given
        $value = self::fullName(['firstName' => '', 'lastName' => str_repeat('a', FullName::MAX_LENGTH + 1)]);

        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(2);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    #[Test]
    public function itRefusesWhenFieldMissing(): void
    {
        // Given
        $fullName = self::fullName();
        unset($fullName['lastName']);

        // When
        $this->validateValue($fullName);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    protected function createCompound(): ValidFullName
    {
        return new ValidFullName();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array{firstName: string, lastName: string}
     */
    private static function fullName(array $overrides = []): array
    {
        return $overrides + [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];
    }

    private function collection(): Assert\Collection
    {
        $normalizer = 'trim';

        return new Assert\Collection(
            fields: [
                'firstName' => [
                    new Assert\NotBlank(normalizer: $normalizer),
                    new Assert\Length(max: FullName::MAX_LENGTH),
                ],
                'lastName' => [
                    new Assert\NotBlank(normalizer: $normalizer),
                    new Assert\Length(max: FullName::MAX_LENGTH),
                ],
            ],
            allowMissingFields: false,
        );
    }
}
