<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidAddress;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidAddress>
 */
final class ValidAddressTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris']);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param array{street: string, postalCode: string, city: string} $value
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(array $value): void
    {
        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([self::collection()]);
    }

    /**
     * @return iterable<string, array{array{street: string, postalCode: string, city: string}}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty street' => [['street' => '', 'postalCode' => '75001', 'city' => 'Paris']];
        yield 'whitespace only street' => [['street' => '   ', 'postalCode' => '75001', 'city' => 'Paris']];
        yield 'empty postal code' => [['street' => '12 rue des Lilas', 'postalCode' => '', 'city' => 'Paris']];
        yield 'empty city' => [['street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => '']];
        yield 'street too long' => [['street' => str_repeat('a', 256), 'postalCode' => '75001', 'city' => 'Paris']];
        yield 'postal code too long' => [['street' => '12 rue des Lilas', 'postalCode' => str_repeat('1', 21), 'city' => 'Paris']];
    }

    protected function createCompound(): ValidAddress
    {
        return new ValidAddress();
    }

    private static function collection(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'street' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: 255),
                ],
                'postalCode' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: 20),
                ],
                'city' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: 255),
                ],
            ],
            allowMissingFields: false,
        );
    }
}
