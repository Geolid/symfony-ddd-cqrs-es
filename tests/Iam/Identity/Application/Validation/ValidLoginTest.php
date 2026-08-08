<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Validation;

use Iam\Identity\Application\Validation\ValidLogin;
use Iam\Identity\Domain\ValueObject\Login;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidLogin>
 */
final class ValidLoginTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsALogin(): void
    {
        // When
        $this->validateValue('operator');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itAcceptsTheMaximumLength(): void
    {
        // When
        $this->validateValue(str_pad('operator', 50, 'operator'));

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedLogins')]
    public function itRefusesALogin(mixed $login, array $rules): void
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
    public static function provideRefusedLogins(): iterable
    {
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), self::valueObject()]];
        yield 'too long' => [str_pad('operator', 51, 'operator'), [new Assert\Length(max: 50), self::valueObject()]];
    }

    protected function createCompound(): ValidLogin
    {
        return new ValidLogin();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Login::class, method: 'fromString');
    }
}
