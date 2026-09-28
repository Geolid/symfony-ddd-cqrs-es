<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\Validation\ValidTotpCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidTotpCode>
 */
final class ValidTotpCodeTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('123456');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $code, Assert\NotBlank|Assert\Type|Assert\Regex $rule): void
    {
        // When
        $this->validateValue($code);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$rule]);
    }

    /**
     * @return iterable<string, array{mixed, Assert\NotBlank|Assert\Type|Assert\Regex}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', new Assert\NotBlank(normalizer: 'trim')];
        yield 'whitespace only' => ['   ', new Assert\NotBlank(normalizer: 'trim')];
        yield 'not a string' => [123456, new Assert\Type('string')];
        yield 'too short' => ['12345', new Assert\Regex('/^\d{6}$/')];
        yield 'too long' => ['1234567', new Assert\Regex('/^\d{6}$/')];
        yield 'non-digit characters' => ['12a456', new Assert\Regex('/^\d{6}$/')];
    }

    protected function createCompound(): ValidTotpCode
    {
        return new ValidTotpCode();
    }
}
