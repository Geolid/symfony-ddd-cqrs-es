<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UniqueValueValidator>
 */
final class UniqueValueValidatorTest extends ConstraintValidatorTestCase
{
    private DummyUniqueValueRegistry $registry;

    #[Test]
    public function itAcceptsAValueNobodyReserved(): void
    {
        // When
        $this->validator->validate('buyer@example.com', new ValidUniqueValue(DummyUniqueKey::EMAIL));

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itReportsAValueAlreadyReserved(): void
    {
        // Given
        $this->registry->reserve(DummyUniqueKey::EMAIL, 'buyer@example.com');

        // When
        $this->validator->validate('buyer@example.com', new ValidUniqueValue(DummyUniqueKey::EMAIL));

        // Then
        $this->buildViolation('Value "{{ value }}" is already in use for {{ type }}.')
            ->setParameter('{{ value }}', 'buyer@example.com')
            ->setParameter('{{ type }}', 'EMAIL')
            ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
            ->assertRaised();
    }

    #[Test]
    #[DataProvider('provideEmptyValues')]
    public function itLeavesAnEmptyValueToTheConstraintThatOwnsIt(mixed $value): void
    {
        // When
        $this->validator->validate($value, new ValidUniqueValue(DummyUniqueKey::EMAIL));

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
    }

    #[Test]
    public function itFailsOnAValueItCannotRead(): void
    {
        // Then
        $this->expectException(UnexpectedValueException::class);

        // When
        $this->validator->validate(42, new ValidUniqueValue(DummyUniqueKey::EMAIL));
    }

    #[Test]
    public function itCarriesTheGroupsAndPayloadItWasGiven(): void
    {
        // When
        $constraint = new ValidUniqueValue(DummyUniqueKey::EMAIL, groups: ['registration'], payload: 'severity');

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
        $this->validator->validate('buyer@example.com', new NotBlank());
    }

    protected function createValidator(): UniqueValueValidator
    {
        $this->registry = new DummyUniqueValueRegistry();

        return new UniqueValueValidator($this->registry);
    }
}

enum DummyUniqueKey: string
{
    case EMAIL = 'dummy.email';
}

final class DummyUniqueValueRegistry implements UniqueValueRegistryInterface
{
    /** @var list<string> */
    private array $reserved = [];

    public function reserve(\BackedEnum $type, string $value): void
    {
        if ($this->exists($type, $value)) {
            throw new UniqueValueAlreadyTakenException($type, $value);
        }

        $this->reserved[] = self::key($type, $value);
    }

    public function release(\BackedEnum $type, string $value): void
    {
        $this->reserved = array_values(array_filter(
            $this->reserved,
            static fn (string $key): bool => $key !== self::key($type, $value),
        ));
    }

    public function exists(\BackedEnum $type, string $value): bool
    {
        return \in_array(self::key($type, $value), $this->reserved, true);
    }

    private static function key(\BackedEnum $type, string $value): string
    {
        return \sprintf('%s:%s', $type->value, $value);
    }
}
