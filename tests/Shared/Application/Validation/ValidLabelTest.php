<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidLabel;
use Shared\Domain\ValueObject\Label;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidLabel>
 */
final class ValidLabelTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $label): void
    {
        // When
        $this->validateValue($label);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'label' => ['Espresso cups, set of 6'];
        yield 'maximum length' => [str_repeat('a', Label::MAX_LENGTH)];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $label, array $rules): void
    {
        // When
        $this->validateValue($label);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [self::notBlank()]];
        yield 'whitespace only' => ['   ', [self::notBlank()]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
        yield 'too long' => [str_repeat('a', Label::MAX_LENGTH + 1), [new Assert\Length(max: Label::MAX_LENGTH)]];
    }

    protected function createCompound(): ValidLabel
    {
        return new ValidLabel();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }
}
