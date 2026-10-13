<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Iam\Identity\Application\Policy\IssueEmailChangeCodeOnEmailChangeRequested;
use Iam\Identity\Domain\Event\IdentityEmailChangeRequested;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Mime\Email;

final class IssueEmailChangeCodeOnEmailChangeRequestedTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $id = IdentityIdFactory::new()->create();
        $newEmail = EmailFactory::new()->create();
        $requestedAt = Clock::get()->now()->modify('+50 minutes');

        // When
        $this->trigger(IssueEmailChangeCodeOnEmailChangeRequested::class, new IdentityEmailChangeRequested($id, $newEmail, $requestedAt));

        // Then
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', $newEmail->value);
        self::assertEmailSubjectContains($message, 'Confirm your new email address');
        self::assertMatchesRegularExpression('/\d{6}/', (string) $message->getTextBody());
    }
}
