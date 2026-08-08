<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Validation;

use Iam\Access\Application\Validation\ValidPermission;
use Iam\Access\Domain\ValueObject\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidPermission>
 */
final class ValidPermissionTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAcceptsAPermission(): void
    {
        // When
        $this->validateValue('fixture.widget:read');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itAcceptsTheMaximumLength(): void
    {
        // When
        $this->validateValue(str_pad('fixture', 85, 'fixture').'.'.str_pad('widget', 84, 'widget').':'.str_pad('write', 84, 'write'));

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedPermissions')]
    public function itRefusesAPermission(mixed $permission, array $rules): void
    {
        // When
        $this->validateValue($permission);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedPermissions(): iterable
    {
        yield 'nothing' => ['', [self::notBlank()]];
        yield 'blanks only' => ['   ', [self::notBlank(), self::regex(), self::valueObject()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::regex(), self::valueObject()]];
        yield 'missing the action segment' => ['fixture.widget', [self::regex(), self::valueObject()]];
        yield 'missing the bc segment' => ['fixture:read', [self::regex(), self::valueObject()]];
        yield 'too long' => [str_pad('fixture', 86, 'fixture').'.'.str_pad('widget', 84, 'widget').':'.str_pad('write', 84, 'write'), [new Assert\Length(max: 255), self::valueObject()]];
    }

    protected function createCompound(): ValidPermission
    {
        return new ValidPermission();
    }

    private static function notBlank(): Assert\NotBlank
    {
        return new Assert\NotBlank(normalizer: 'trim');
    }

    private static function regex(): Assert\Regex
    {
        return new Assert\Regex(pattern: Permission::PATTERN, message: 'A permission must be formatted "<subdomain>.<bc>:<action>".');
    }

    private static function valueObject(): ValidValueObject
    {
        return new ValidValueObject(Permission::class);
    }
}
