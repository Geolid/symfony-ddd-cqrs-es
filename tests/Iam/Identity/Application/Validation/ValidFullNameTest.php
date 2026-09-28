<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Validation;

use Iam\Identity\Application\Validation\ValidFullName;
use Iam\Identity\Domain\ValueObject\FullName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidFullName>
 */
final class ValidFullNameTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $fullName): void
    {
        // When
        $this->validateValue($fullName);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'full name' => ['Jane Doe'];
        yield 'maximum length' => [str_repeat('a', FullName::MAX_LENGTH)];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $fullName, array $rules): void
    {
        // When
        $this->validateValue($fullName);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'whitespace only' => ['   ', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
        yield 'too long' => [str_repeat('a', FullName::MAX_LENGTH + 1), [new Assert\Length(max: FullName::MAX_LENGTH)]];
    }

    protected function createCompound(): ValidFullName
    {
        return new ValidFullName();
    }
}
