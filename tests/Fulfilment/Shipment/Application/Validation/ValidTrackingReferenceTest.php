<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Validation;

use Fulfilment\Shipment\Application\Validation\ValidTrackingReference;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidTrackingReference>
 */
final class ValidTrackingReferenceTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('ACME-1234567890');

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $trackingReference, array $rules): void
    {
        // When
        $this->validateValue($trackingReference);

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
        yield 'longer than the carrier can issue' => [str_repeat('A', 65), [self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::valueObject()]];
    }

    protected function createCompound(): ValidTrackingReference
    {
        return new ValidTrackingReference();
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(TrackingReference::class);
    }
}
