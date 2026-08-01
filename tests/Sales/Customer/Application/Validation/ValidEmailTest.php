<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Validation\ValidEmail;
use Sales\Customer\Domain\Email;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidEmail>
 */
final class ValidEmailTest extends CompoundConstraintTestCase
{
    protected function createCompound(): ValidEmail
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

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedAddresses')]
    public function itRefusesAnAddress(mixed $address, array $rules): void
    {
        // When
        $this->validateValue($address);

        // Then
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedAddresses(): iterable
    {
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), self::valueObject()]];
        yield 'out of the address format' => ['buyer-at-example.com', [self::valueObject()]];
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Email::class, method: 'fromString');
    }
}
