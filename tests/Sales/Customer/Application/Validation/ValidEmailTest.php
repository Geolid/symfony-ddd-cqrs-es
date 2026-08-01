<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Customer\Application\Validation\ValidEmail;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ValidEmailTest extends TestCase
{
    #[Test]
    public function itAcceptsAnAddress(): void
    {
        // Then
        self::assertCount(0, self::validate('buyer@example.com'));
    }

    #[Test]
    #[DataProvider('provideRefusedAddresses')]
    public function itRefusesAnAddress(mixed $address, int $violations): void
    {
        // Then
        self::assertCount($violations, self::validate($address));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function provideRefusedAddresses(): iterable
    {
        yield 'nothing' => ['', 1];
        yield 'blanks only' => ['   ', 2];
        yield 'a value out of the address format' => ['buyer-at-example.com', 1];
    }

    private static function validate(mixed $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate($value, new ValidEmail());
    }
}
