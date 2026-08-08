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
    public function itAcceptsAtLeastOnePermission(): void
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
    #[DataProvider('provideRefusedPermissions')]
    public function itRefusesPermissions(mixed $permissions, array $rules): void
    {
        // When
        $this->validateValue($permissions);

        // Then
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedPermissions(): iterable
    {
        yield 'no permission at all' => [[], [new Assert\Count(min: 1)]];
        yield 'a countable that is not an array' => [new \ArrayObject(['fixture.widget:read']), [new Assert\Type('array')]];
        yield 'a malformed permission' => [['fixture.widget'], [self::permissionShape()]];
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
