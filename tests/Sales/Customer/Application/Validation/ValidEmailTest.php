<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Validation\ValidEmail;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

final class ValidEmailTest extends CompoundConstraintTestCase
{
    public function createCompound(): Compound
    {
        return new ValidEmail();
    }

    #[Test]
    public function itAcceptsAnAddress(): void
    {
        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedAddresses')]
    public function itRefusesAnAddress(mixed $address, string $code): void
    {
        // When
        $this->validateValue($address);

        // Then
        $this->assertViolationIsRaisedByCompound($code);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideRefusedAddresses(): iterable
    {
        yield 'nothing' => ['', Assert\NotBlank::IS_BLANK_ERROR];
        yield 'blanks only' => ['   ', Assert\NotBlank::IS_BLANK_ERROR];
        yield 'out of the address format' => ['buyer-at-example.com', ValidValueObject::DOMAIN_VALIDATION_ERROR];
    }
}
