<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Validation;

use Fulfilment\Shipment\Application\Validation\ValidShipmentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

final class ValidShipmentStatusTest extends CompoundConstraintTestCase
{
    public function createCompound(): Compound
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

    #[Test]
    #[DataProvider('provideRefusedStatuses')]
    public function itRefusesAStatus(mixed $status, string $code): void
    {
        // When
        $this->validateValue($status);

        // Then
        $this->assertViolationIsRaisedByCompound($code);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideRefusedStatuses(): iterable
    {
        yield 'outside the vocabulary' => ['teleported', Assert\Choice::NO_SUCH_CHOICE_ERROR];
        yield 'refused by the value object' => ['teleported', ValidValueObject::DOMAIN_VALIDATION_ERROR];
        yield 'not a string' => [42, Assert\Type::INVALID_TYPE_ERROR];
    }
}
