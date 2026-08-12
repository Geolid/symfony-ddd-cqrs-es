<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Validation;

use Iam\Identity\Application\Validation\ValidLabel;
use Iam\Identity\Domain\ValueObject\Label;
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
        yield 'label' => ['CI pipeline'];
        yield 'maximum length' => [str_pad('CI pipeline', 255, 'x')];
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
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::valueObject()]];
        yield 'too long' => [str_pad('CI pipeline', 256, 'x'), [new Assert\Length(max: 255), self::valueObject()]];
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
