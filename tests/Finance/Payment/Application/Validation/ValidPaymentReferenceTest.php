<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Validation;

use Finance\Payment\Application\Validation\ValidPaymentReference;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidPaymentReference>
 */
final class ValidPaymentReferenceTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('GLBX-9F3K2M1P');

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $reference, array $rules): void
    {
        // When
        $this->validateValue($reference);

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
        yield 'too long' => [str_repeat('A', PaymentReference::MAX_LENGTH + 1), [new Assert\Length(max: PaymentReference::MAX_LENGTH)]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
    }

    protected function createCompound(): ValidPaymentReference
    {
        return new ValidPaymentReference();
    }
}
