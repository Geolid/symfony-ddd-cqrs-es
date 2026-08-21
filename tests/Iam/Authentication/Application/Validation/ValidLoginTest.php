<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\Validation\ValidLogin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidLogin>
 */
final class ValidLoginTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $login): void
    {
        // When
        $this->validateValue($login);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'login' => ['ada.lovelace'];
        yield 'maximum length' => [str_repeat('a', 50)];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $login, array $rules): void
    {
        // When
        $this->validateValue($login);

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
        yield 'too long' => [str_repeat('a', 51), [new Assert\Length(max: 50)]];
    }

    protected function createCompound(): ValidLogin
    {
        return new ValidLogin();
    }
}
