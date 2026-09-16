<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class RegistrationControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheRegisterForm(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_register'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="register-form"]')->count());
    }

    #[Test]
    public function itRegisters(): void
    {
        // Given
        $login = \sprintf('test-%s', Uuid::uuid7()->toString());
        $client = self::browser();
        $crawler = $client->request('GET', $this->path('storefront_register'));
        $form = $crawler->filter('[data-testid="register-form"]')->form();

        // When
        $form->setValues(['register[login]' => $login, 'register[password]' => 'MyStr0ngP@ssw0rd123!']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofLogin($login);
        self::assertSame($login, $result->login);
    }

    #[Test]
    public function itRefusesWhenLoginAlreadyTaken(): void
    {
        // Given
        $client = self::browser();
        $login = \sprintf('test-%s', Uuid::uuid7()->toString());
        $existingIdentityId = Uuid::uuid7()->toString();
        $commandBus = $this->service(CommandBusInterface::class);
        $commandBus->dispatch(new RegisterIdentity($existingIdentityId));
        $commandBus->dispatch(new DefinePasswordCredential($existingIdentityId, $login, 'AnExistingStr0ngP@ss1!'));
        $crawler = $client->request('GET', $this->path('storefront_register'));
        $form = $crawler->filter('[data-testid="register-form"]')->form();

        // When
        $form->setValues(['register[login]' => $login, 'register[password]' => 'AnotherStr0ngP@ss1!']);
        $client->submit($form);

        // Then
        self::assertResponseStatusCodeSame(422);
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="register-login"][aria-invalid="true"]')->count());
    }
}
