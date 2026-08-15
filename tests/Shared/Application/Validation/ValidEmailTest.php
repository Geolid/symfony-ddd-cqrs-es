<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidEmail;
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
        yield 'whitespace only' => ['   ', [self::notBlank(), new Assert\Email()]];
        yield 'not a string' => [42, [new Assert\Type('string'), new Assert\Email()]];
        yield 'out of the address format' => ['buyer-at-example.com', [new Assert\Email()]];
        yield 'too long' => ['buyer@'.rtrim(str_repeat('example.com.', 22), '.'), [new Assert\Length(max: 255)]];
    }

    protected function createCompound(): ValidEmail
    {
        return new ValidEmail();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }
}
