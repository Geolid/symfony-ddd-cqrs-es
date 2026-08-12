<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Validation\ValidPaymentReference;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Application\Validation\ValidValueObject;
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
        yield 'nothing' => ['', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'longer than the provider can issue' => [str_repeat('A', 65), [self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::valueObject()]];
    }

    protected function createCompound(): ValidPaymentReference
    {
        return new ValidPaymentReference();
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(PaymentReference::class);
    }
}
