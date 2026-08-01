<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Validation\ValidCustomerId;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

final class ValidCustomerIdTest extends CompoundConstraintTestCase
{
    public function createCompound(): Compound
    {
        return new ValidCustomerId();
    }

    #[Test]
    public function itAcceptsAnIdentifier(): void
    {
        // When
        $this->validateValue(Uuid::uuid7()->toString());

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedIdentifiers')]
    public function itRefusesAnIdentifier(mixed $id, string $code): void
    {
        // When
        $this->validateValue($id);

        // Then
        $this->assertViolationIsRaisedByCompound($code);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideRefusedIdentifiers(): iterable
    {
        yield 'nothing' => ['', Assert\NotBlank::IS_BLANK_ERROR];
        yield 'blanks only' => ['   ', Assert\NotBlank::IS_BLANK_ERROR];
        yield 'not a string' => [42, Assert\Type::INVALID_TYPE_ERROR];
        yield 'out of the identifier format' => ['not-a-uuid', ValidValueObject::DOMAIN_VALIDATION_ERROR];
    }
}
