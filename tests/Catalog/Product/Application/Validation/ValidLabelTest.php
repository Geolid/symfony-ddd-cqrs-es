<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Validation;

use Catalog\Product\Application\Validation\ValidLabel;
use Catalog\Product\Domain\ValueObject\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidLabel>
 */
final class ValidLabelTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsALabel(): void
    {
        // When
        $this->validateValue('Espresso cups, set of 6');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itAcceptsTheMaximumLength(): void
    {
        // When
        $this->validateValue(str_repeat('a', 255));

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedLabels')]
    public function itRefusesALabel(mixed $label, array $rules): void
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
    public static function provideRefusedLabels(): iterable
    {
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), self::valueObject()]];
        yield 'too long' => [str_repeat('a', 256), [new Assert\Length(max: 255), self::valueObject()]];
    }

    protected function createCompound(): ValidLabel
    {
        return new ValidLabel();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Label::class, method: 'fromString');
    }
}
