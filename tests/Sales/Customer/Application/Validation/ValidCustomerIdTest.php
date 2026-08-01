<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Validation\ValidCustomerId;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ValidCustomerIdTest extends TestCase
{
    #[Test]
    public function itAcceptsAnIdentifier(): void
    {
        // Then
        self::assertCount(0, self::validate(Uuid::uuid7()->toString()));
    }

    #[Test]
    #[DataProvider('provideRefusedIdentifiers')]
    public function itRefusesAnIdentifier(mixed $id, int $violations): void
    {
        // Then
        self::assertCount($violations, self::validate($id));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function provideRefusedIdentifiers(): iterable
    {
        yield 'nothing' => ['', 1];
        yield 'blanks only' => ['   ', 2];
        yield 'a value that is not a string' => [42, 2];
        yield 'a value out of the identifier format' => ['not-a-uuid', 1];
    }

    private static function validate(mixed $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new ValidCustomerId());
    }
}
