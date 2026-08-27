<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidFullName;
use Shared\Domain\ValueObject\FullName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidFullName>
 */
final class ValidFullNameTest extends CompoundConstraintTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue(['firstName' => 'Ada', 'lastName' => 'Lovelace']);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @param array{firstName: string, lastName: string} $value
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(array $value): void
    {
        // When
        $this->validateValue($value);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    /**
     * @return iterable<string, array{array{firstName: string, lastName: string}}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty first name' => [['firstName' => '', 'lastName' => 'Lovelace']];
        yield 'whitespace only first name' => [['firstName' => '   ', 'lastName' => 'Lovelace']];
        yield 'empty last name' => [['firstName' => 'Ada', 'lastName' => '']];
        yield 'first name too long' => [['firstName' => str_repeat('a', FullName::MAX_LENGTH + 1), 'lastName' => 'Lovelace']];
        yield 'last name too long' => [['firstName' => 'Ada', 'lastName' => str_repeat('a', FullName::MAX_LENGTH + 1)]];
    }

    #[Test]
    public function itDoesNotStopAtTheFirstInvalidField(): void
    {
        // When
        $this->validateValue(['firstName' => '', 'lastName' => str_repeat('a', FullName::MAX_LENGTH + 1)]);

        // Then
        $this->assertViolationsCount(2);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    #[Test]
    public function itRefusesWhenAFieldIsMissing(): void
    {
        // When
        $this->validateValue(['firstName' => 'Ada']);

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([$this->collection()]);
    }

    protected function createCompound(): ValidFullName
    {
        return new ValidFullName();
    }

    private function collection(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'firstName' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: FullName::MAX_LENGTH),
                ],
                'lastName' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(max: FullName::MAX_LENGTH),
                ],
            ],
            allowMissingFields: false,
        );
    }
}
