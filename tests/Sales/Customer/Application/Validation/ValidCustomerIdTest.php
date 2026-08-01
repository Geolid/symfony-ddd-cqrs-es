<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Validation\ValidCustomerId;
use Sales\Customer\Domain\CustomerId;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidCustomerId>
 */
final class ValidCustomerIdTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsAnIdentifier(): void
    {
        // When
        $this->validateValue(Uuid::uuid7()->toString());

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedIdentifiers')]
    public function itRefusesAnIdentifier(mixed $id, array $rules): void
    {
        // When
        $this->validateValue($id);

        // Then
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedIdentifiers(): iterable
    {
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), new Assert\Uuid(strict: false), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), new Assert\Uuid(strict: false), self::valueObject()]];
        yield 'out of the identifier format' => ['not-a-uuid', [new Assert\Uuid(strict: false), self::valueObject()]];
    }

    protected function createCompound(): ValidCustomerId
    {
        return new ValidCustomerId();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(CustomerId::class);
    }
}
