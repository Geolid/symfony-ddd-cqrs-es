<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Iam\Identity\Application\Policy\IssueConfirmationOnIdentityConfirmationRequested;
use Iam\Identity\Domain\Event\IdentityConfirmationRequested;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Mime\Email;

final class IssueConfirmationOnIdentityConfirmationRequestedTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->trigger(
            IssueConfirmationOnIdentityConfirmationRequested::class,
            new IdentityConfirmationRequested($identity->id, Clock::get()->now()),
        );

        // Then
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', $builder['email']->value);
        self::assertEmailSubjectContains($message, 'Confirm your email address');
        self::assertMatchesRegularExpression('/\d{6}/', (string) $message->getTextBody());
    }
}
