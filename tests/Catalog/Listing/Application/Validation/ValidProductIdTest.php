<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Validation;

use Catalog\Listing\Application\Validation\ValidProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidProductId>
 */
final class ValidProductIdTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
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
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $id, array $rules): void
    {
        // When
        $this->validateValue($id);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [new Assert\NotBlank()]];
        yield 'whitespace only' => ['   ', [new Assert\Uuid()]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
        yield 'invalid uuid' => ['not-a-uuid', [new Assert\Uuid()]];
    }

    protected function createCompound(): ValidProductId
    {
        return new ValidProductId();
    }
}
