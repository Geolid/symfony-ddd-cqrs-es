<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidEmail;
use Shared\Application\Validation\ValidValueObject;
use Shared\Domain\ValueObject\Email;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidEmail>
 */
final class ValidEmailTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $address, array $rules): void
    {
        // When
        $this->validateValue($address);

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
        yield 'whitespace only' => ['   ', [self::notBlank(), new Assert\Email(), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), new Assert\Email(), self::valueObject()]];
        yield 'out of the address format' => ['buyer-at-example.com', [new Assert\Email(), self::valueObject()]];
        yield 'too long' => ['buyer@'.rtrim(str_repeat('example.com.', 22), '.'), [new Assert\Length(max: 255), self::valueObject()]];
    }

    protected function createCompound(): ValidEmail
    {
        return new ValidEmail();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Email::class, method: 'fromString');
    }
}
