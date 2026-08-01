<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Validation;

use Fulfilment\Shipment\Application\Validation\ValidShipmentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ValidShipmentStatusTest extends TestCase
{
    #[Test]
    #[DataProvider('providePublishedStatuses')]
    public function itAcceptsAStatusOfTheVocabulary(string $status): void
    {
        // Then
        self::assertCount(0, self::validate($status));
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
    public function itRefusesAStatus(mixed $status, int $violations): void
    {
        // Then
        self::assertCount($violations, self::validate($status));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function provideRefusedStatuses(): iterable
    {
        yield 'a word outside the vocabulary' => ['teleported', 2];
        yield 'a value that is not a string' => [42, 3];
    }

    private static function validate(mixed $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new ValidShipmentStatus());
    }
}
