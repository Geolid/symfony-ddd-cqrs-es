<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Iam\Identity\Application\Policy\IssueConfirmationOnIdentityRegistered;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mime\Email;

final class IssueConfirmationOnIdentityRegisteredTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $id = IdentityId::fromString(Uuid::uuid7()->toString());
        $fullName = IdentityBuilder::sample('fullName');
        $email = IdentityBuilder::sample('email');
        $registeredAt = IdentityBuilder::sample('registeredAt');

        // When
        $this->trigger(IssueConfirmationOnIdentityRegistered::class, new IdentityRegistered($id, $fullName, $email, $registeredAt));

        // Then
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', $email->value);
        self::assertEmailSubjectContains($message, 'Confirm your email address');
        self::assertMatchesRegularExpression('/\d{6}/', (string) $message->getTextBody());
    }
}
