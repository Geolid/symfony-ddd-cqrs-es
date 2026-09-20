<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Support\Faker\SeededFaker;
use Symfony\Component\Clock\Clock;

final class RegistrationControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheRegisterForm(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_register', ['email' => SeededFaker::get()->unique()->safeEmail()]));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="register-form"]')->count());
    }

    #[Test]
    public function itRedirectsToIdentifyWhenEmailMissing(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_register'));

        // Then
        self::assertResponseRedirects($this->path('security_login'));
    }

    #[Test]
    public function itRegistersAndConfirms(): void
    {
        // Given
        $email = SeededFaker::get()->unique()->safeEmail();
        $client = self::browser();
        $crawler = $client->request('GET', $this->path('storefront_register', ['email' => $email]));
        $form = $crawler->filter('[data-testid="register-form"]')->form();

        // When
        $form->setValues([
            'register[fullName]' => 'Jane Doe',
            'register[password][first]' => 'MyStr0ngP@ssw0rd123!',
            'register[password][second]' => 'MyStr0ngP@ssw0rd123!',
        ]);
        $client->submit($form);
        $crawler = $client->followRedirect();

        // Then
        $identity = $this->service(IdentityFinderInterface::class)->ofEmailOrNull($email);
        self::assertNotNull($identity);
        self::assertGreaterThan(0, $crawler->filter('[data-testid="confirmation-form"]')->count());

        $code = $this->service(NativeCodeChallenger::class)->issue(
            VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id),
            Clock::get()->now(),
        );
        $form = $crawler->filter('[data-testid="confirmation-form"]')->form();
        $form->setValues(['confirmation[code]' => $code]);
        $client->submit($form);

        self::assertResponseRedirects($this->path('security_login'));
    }

    #[Test]
    public function itRefusesWhenPasswordsMismatch(): void
    {
        // Given
        $client = self::browser();
        $crawler = $client->request('GET', $this->path('storefront_register', ['email' => SeededFaker::get()->unique()->safeEmail()]));
        $form = $crawler->filter('[data-testid="register-form"]')->form();

        // When
        $form->setValues([
            'register[fullName]' => 'Jane Doe',
            'register[password][first]' => 'MyStr0ngP@ssw0rd123!',
            'register[password][second]' => 'AnotherStr0ngP@ss1!',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseStatusCodeSame(422);
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="register-password"][aria-invalid="true"]')->count());
    }

    #[Test]
    public function itRefusesWhenEmailAlreadyTaken(): void
    {
        // Given
        $client = self::browser();
        $email = SeededFaker::get()->unique()->safeEmail();
        $this->service(CommandBusInterface::class)->dispatch(new RegisterIdentity(Uuid::uuid7()->toString(), 'Existing Person', $email));
        $crawler = $client->request('GET', $this->path('storefront_register', ['email' => $email]));
        $form = $crawler->filter('[data-testid="register-form"]')->form();

        // When
        $form->setValues([
            'register[fullName]' => 'Jane Doe',
            'register[password][first]' => 'AnotherStr0ngP@ss1!',
            'register[password][second]' => 'AnotherStr0ngP@ss1!',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseStatusCodeSame(422);
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="register-email"][aria-invalid="true"]')->count());
    }

    #[Test]
    public function itShowsTheConfirmationForm(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $client->request('GET', $this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="confirmation-form"]')->count());
    }

    #[Test]
    public function itRefusesWithWrongCode(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $crawler = $client->request('GET', $this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
        $form = $crawler->filter('[data-testid="confirmation-form"]')->form();

        // When
        $form->setValues(['confirmation[code]' => '000000']);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-error"]')->count());
    }

    #[Test]
    public function itResendsTheConfirmationCode(): void
    {
        // Given
        $client = self::browser();
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-2 minutes'))->create();
        $this->store($identity);
        $crawler = $client->request('GET', $this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
        $form = $crawler->filter('[data-testid="confirmation-resend-form"]')->form();

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
        $client->followRedirect();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-success"]')->count());
    }

    #[Test]
    public function itRefusesToResendTooSoon(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $crawler = $client->request('GET', $this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
        $form = $crawler->filter('[data-testid="confirmation-resend-form"]')->form();

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
        $client->followRedirect();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-error"]')->count());
    }
}
