<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class CustomerControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itRegistersACustomerAndShowsItInTheList(): void
    {
        // Given
        $client = self::browser();

        // When
        $this->registerCustomer($client, 'buyer-1@example.com');

        // Then
        self::assertResponseRedirects('/customers');
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="customer-email"]', 'buyer-1@example.com');
    }

    #[Test]
    public function itRefusesAnAddressAlreadyRegistered(): void
    {
        // Given
        $client = self::browser();
        $this->registerCustomer($client, 'buyer-2@example.com');

        // When
        $crawler = $client->request('GET', '/customers/new');
        $form = $crawler->filter('form')->form();
        $form->setValues([\sprintf('%s[email]', $form->getName()) => 'buyer-2@example.com']);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="flash-error"]');
    }

    #[Test]
    public function itErasesACustomer(): void
    {
        // Given
        $client = self::browser();
        $id = $this->registerCustomer($client, 'buyer-3@example.com');

        // When
        $client->request('POST', \sprintf('/customers/%s/erase', $id), [
            '_token' => $this->csrfToken($client, 'erase-customer-'.$id),
        ]);

        // Then
        self::assertResponseRedirects('/customers');
        $client->followRedirect();
        self::assertSelectorTextNotContains('[data-testid="customer-email"]', 'buyer-3@example.com');
    }

    #[Test]
    public function itRefusesToEraseWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $id = $this->registerCustomer($client, 'buyer-4@example.com');

        // When
        $client->request('POST', \sprintf('/customers/%s/erase', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    private function registerCustomer(KernelBrowser $client, string $email): string
    {
        $crawler = $client->request('GET', '/customers/new');
        $form = $crawler->filter('form')->form();
        $form->setValues([\sprintf('%s[email]', $form->getName()) => $email]);
        $client->submit($form);

        foreach ($this->service(CustomerFinderInterface::class) as $customer) {
            if ($email === $customer->email) {
                return $customer->id;
            }
        }

        self::fail(\sprintf('Customer "%s" was not registered.', $email));
    }
}
