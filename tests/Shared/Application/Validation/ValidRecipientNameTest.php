<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Validation\ValidRecipientName;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

/**
 * @extends CompoundConstraintTestCase<ValidRecipientName>
 */
final class ValidRecipientNameTest extends CompoundConstraintTestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $recipientName): void
    {
        // When
        $this->validateValue($recipientName);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'recipient name' => ['Ada Lovelace'];
        yield 'maximum length' => [str_repeat('a', PostalAddress::RECIPIENT_NAME_MAX_LENGTH)];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $recipientName, array $rules): void
    {
        // When
        $this->validateValue($recipientName);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'whitespace only' => ['   ', [new Assert\NotBlank(normalizer: 'trim')]];
        yield 'too long' => [str_repeat('a', PostalAddress::RECIPIENT_NAME_MAX_LENGTH + 1), [new Assert\Length(max: PostalAddress::RECIPIENT_NAME_MAX_LENGTH)]];
    }

    protected function createCompound(): ValidRecipientName
    {
        return new ValidRecipientName();
    }
}
