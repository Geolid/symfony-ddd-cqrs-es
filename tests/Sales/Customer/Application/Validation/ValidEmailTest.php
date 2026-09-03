<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Validation\ValidEmail;
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
        yield 'empty string' => ['', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'whitespace only' => ['   ', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
        yield 'out of the address format' => ['buyer-at-example.com', [new Assert\Email()]];
    }

    protected function createCompound(): ValidEmail
    {
        return new ValidEmail();
    }
}
