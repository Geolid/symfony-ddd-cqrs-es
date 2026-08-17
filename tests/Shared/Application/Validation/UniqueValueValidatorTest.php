<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
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
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), 'buyer@example.com', 'owner-id');

        // When
        $this->validator->validate('buyer@example.com', new ValidUniqueValue(DummyUniqueKey::EMAIL));

        // Then
        $this->buildViolation('Value "{{ value }}" is already in use for {{ key }}.')
            ->setParameter('{{ value }}', 'buyer@example.com')
            ->setParameter('{{ key }}', 'EMAIL')
            ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
            ->assertRaised();
    }

    #[Test]
    public function itReportsAValueAlreadyReservedUnderACompositeKey(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL, 'scope'), 'buyer@example.com', 'owner-id');

        // When
        $this->validator->validate('buyer@example.com', new ValidUniqueValue(DummyUniqueKey::EMAIL, ['scope']));

        // Then
        $this->buildViolation('Value "{{ value }}" is already in use for {{ key }}.')
            ->setParameter('{{ value }}', 'buyer@example.com')
            ->setParameter('{{ key }}', 'EMAIL')
            ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
            ->assertRaised();
    }

    #[Test]
    public function itIgnoresAValueAlreadyOwnedByTheEditedObjectItself(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), 'buyer@example.com', 'owner-id');
        $this->setObject(new DummyEditedObject('owner-id'));

        // When
        $this->validator->validate(
            'buyer@example.com',
            new ValidUniqueValue(DummyUniqueKey::EMAIL, excludeOwnerIdPropertyPath: 'id'),
        );

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itReportsAValueOwnedBySomeoneElseWhileEditing(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), 'buyer@example.com', 'someone-elses-id');
        $this->setObject(new DummyEditedObject('owner-id'));

        // When
        $this->validator->validate(
            'buyer@example.com',
            new ValidUniqueValue(DummyUniqueKey::EMAIL, excludeOwnerIdPropertyPath: 'id'),
        );

        // Then
        $this->buildViolation('Value "{{ value }}" is already in use for {{ key }}.')
            ->setParameter('{{ value }}', 'buyer@example.com')
            ->setParameter('{{ key }}', 'EMAIL')
            ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
            ->assertRaised();
    }

    #[Test]
    public function itFailsOnExclusionWithoutAnObject(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->validator->validate(
            'buyer@example.com',
            new ValidUniqueValue(DummyUniqueKey::EMAIL, excludeOwnerIdPropertyPath: 'id'),
        );
    }

    #[Test]
    public function itFailsOnANonStringExcludedOwnerProperty(): void
    {
        // Given
        $this->setObject(new DummyEditedObjectWithNonStringId(42));

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->validator->validate(
            'buyer@example.com',
            new ValidUniqueValue(DummyUniqueKey::EMAIL, excludeOwnerIdPropertyPath: 'id'),
        );
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

final readonly class DummyEditedObject
{
    public function __construct(public string $id)
    {
    }
}

final readonly class DummyEditedObjectWithNonStringId
{
    public function __construct(public int $id)
    {
    }
}

final class DummyUniqueValueRegistry implements UniqueValueRegistryInterface
{
    /** @var array<string, string> */
    private array $reserved = [];

    public function reserve(UniqueKey $key, string $value, string $ownerId, ?string $subjectId = null): void
    {
        if ($this->exists($key, $value)) {
            throw UniqueValueAlreadyTakenException::forValue($key, $value);
        }

        $this->reserved[self::normalize($key, $value)] = $ownerId;
    }

    public function release(UniqueKey $key, string $value, string $ownerId): void
    {
        unset($this->reserved[self::normalize($key, $value)]);
    }

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $existingOwnerId = $this->reserved[self::normalize($key, $value)] ?? null;

        if (null === $existingOwnerId) {
            return false;
        }

        return $existingOwnerId !== $excludeOwnerId;
    }

    public function releaseAllForSubject(string $subjectId): void
    {
    }

    private static function normalize(UniqueKey $key, string $value): string
    {
        return \sprintf('%s:%s', $key->toString(), $value);
    }
}
