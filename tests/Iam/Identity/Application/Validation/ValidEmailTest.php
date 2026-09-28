<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Validation;

use Iam\Identity\Application\Validation\ValidEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidEmail>
 */
final class ValidEmailTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('jane.doe@example.test');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $email, Assert\NotBlank|Assert\Type|Assert\Email $rule): void
    {
        // When
        $this->validateValue($email);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$rule]);
    }

    /**
     * @return iterable<string, array{mixed, Assert\NotBlank|Assert\Type|Assert\Email}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', new Assert\NotBlank(normalizer: 'trim')];
        yield 'whitespace only' => ['   ', new Assert\NotBlank(normalizer: 'trim')];
        yield 'not a string' => [42, new Assert\Type('string')];
        yield 'missing domain' => ['jane.doe@', new Assert\Email()];
        yield 'missing at sign' => ['jane.doe.example.test', new Assert\Email()];
    }

    protected function createCompound(): ValidEmail
    {
        return new ValidEmail();
    }
}
