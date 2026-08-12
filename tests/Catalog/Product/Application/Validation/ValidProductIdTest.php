<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Validation;

use Catalog\Product\Application\Validation\ValidProductId;
use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Validation\ValidValueObject;
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
        yield 'nothing' => ['', [new Assert\NotBlank()]];
        yield 'blanks only' => ['   ', [new Assert\Uuid(strict: false), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), new Assert\Uuid(strict: false), self::valueObject()]];
        yield 'out of the UUID format' => ['not-a-uuid', [new Assert\Uuid(strict: false), self::valueObject()]];
    }

    protected function createCompound(): ValidProductId
    {
        return new ValidProductId();
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(ProductId::class, method: 'fromString');
    }
}
