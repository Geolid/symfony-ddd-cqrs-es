<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Iam\Identity\Application\Policy\IssueConfirmationOnIdentityRegistered;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Mime\Email;

final class IssueConfirmationOnIdentityRegisteredTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $id = IdentityIdFactory::new()->create();
        $fullName = FullNameFactory::new()->create();
        $email = EmailFactory::new()->create();
        $registeredAt = Clock::get()->now();

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
