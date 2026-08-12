<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Validation;

use Iam\Access\Application\Validation\ValidPermission;
use Iam\Access\Application\Validation\ValidPermissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidPermissions>
 */
final class ValidPermissionsTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(['fixture.widget:read']);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $permissions, array $rules, int $violationCount): void
    {
        // When
        $this->validateValue($permissions);

        // Then
        $this->assertViolationsCount($violationCount);
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>, int}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'no permission at all' => [[], [new Assert\Count(min: 1)], 1];
        yield 'a countable that is not an array' => [new \ArrayObject(['fixture.widget:read']), [new Assert\Type('array')], 1];
        // A malformed permission trips both the format Regex and the closing ValidValueObject net inside the nested ValidPermission compound.
        yield 'a malformed permission' => [['fixture.widget'], [self::permissionShape()], 2];
    }

    protected function createCompound(): ValidPermissions
    {
        return new ValidPermissions();
    }

    private static function permissionShape(): Assert\All
    {
        return new Assert\All([new ValidPermission()]);
    }
}
