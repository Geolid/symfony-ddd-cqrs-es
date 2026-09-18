<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\IssuePasswordResetOnPasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Mime\Email;

final class IssuePasswordResetOnPasswordCredentialResetRequestedTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $builder = IdentityBuilder::new()->activated();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->trigger(
            IssuePasswordResetOnPasswordCredentialResetRequested::class,
            new PasswordCredentialResetRequested(
                PasswordCredentialId::forIdentity($identity->id->toString()),
                $identity->id->toString(),
                Clock::get()->now(),
            ),
        );

        // Then
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', $builder['email']->value);
        self::assertEmailSubjectContains($message, 'Reset your password');
        self::assertMatchesRegularExpression('/\d{6}/', (string) $message->getTextBody());
    }
}
