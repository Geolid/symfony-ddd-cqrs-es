<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Validation;

use Iam\Access\Application\Validation\ValidPermission;
use Iam\Access\Domain\ValueObject\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidPermission>
 */
final class ValidPermissionTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $permission): void
    {
        // When
        $this->validateValue($permission);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'permission' => ['fixture.widget:read'];
        yield 'maximum length' => [str_pad('fixture.widget:write', 64, '_')];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $permission, array $rules): void
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
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [self::notBlank()]];
        yield 'whitespace only' => ['   ', [self::notBlank(), self::regex()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::regex()]];
        yield 'missing the action segment' => ['fixture.widget', [self::regex()]];
        yield 'missing the bc segment' => ['fixture:read', [self::regex()]];
        yield 'too long' => [str_pad('fixture.widget:write', 65, '_'), [new Assert\Length(max: 64)]];
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
}
