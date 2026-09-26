<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Iam\Identity\Application\Policy\IssueEmailChangeCodeOnEmailChangeRequested;
use Iam\Identity\Domain\Event\IdentityEmailChangeRequested;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mime\Email;

final class IssueEmailChangeCodeOnEmailChangeRequestedTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $id = IdentityId::fromString(Uuid::uuid7()->toString());
        $newEmail = IdentityBuilder::sample('email');
        $requestedAt = IdentityBuilder::sample('emailChangeRequestedAt');

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
