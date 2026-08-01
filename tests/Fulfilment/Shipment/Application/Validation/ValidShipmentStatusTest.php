<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Validation;

use Fulfilment\Shipment\Application\Language\PublishedShipmentStatus;
use Fulfilment\Shipment\Application\Validation\ValidShipmentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidShipmentStatus>
 */
final class ValidShipmentStatusTest extends CompoundConstraintTestCase
{
    protected function createCompound(): ValidShipmentStatus
    {
        return new ValidShipmentStatus();
    }

    #[Test]
    #[DataProvider('providePublishedStatuses')]
    public function itAcceptsAStatusOfTheVocabulary(string $status): void
    {
        // When
        $this->validateValue($status);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providePublishedStatuses(): iterable
    {
        yield 'pending' => ['pending'];
        yield 'dispatched' => ['dispatched'];
        yield 'delivered' => ['delivered'];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedStatuses')]
    public function itRefusesAStatus(mixed $status, array $rules): void
    {
        // When
        $this->validateValue($status);

        // Then
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedStatuses(): iterable
    {
        yield 'outside the vocabulary' => ['teleported', [self::choice(), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::choice(), self::valueObject()]];
    }

    private static function choice(): Assert\Choice
    {
        return new Assert\Choice(choices: array_column(PublishedShipmentStatus::cases(), 'value'));
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(PublishedShipmentStatus::class, method: 'from');
    }
}
