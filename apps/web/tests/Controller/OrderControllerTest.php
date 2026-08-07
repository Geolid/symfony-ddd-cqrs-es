<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Application\Enum\AppOrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    private const string CHECKOUT_URL = 'https://checkout.test/session/GLBX-TEST-REF';

    private static ?string $lastReturnUrl = null;

    #[Test]
    public function itPlacesAnOrderAndRedirectsToCheckout(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-1@example.com'));

        // When
        $this->placeOrder($client);

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itShowsThePlacedOrderInTheList(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-2@example.com'));
        $this->placeOrder($client);

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
    }

    #[Test]
    public function itResumesPaymentForAPendingOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-3@example.com'));
        $id = $this->placeOrder($client);

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $id));

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itRefusesToResumePaymentWhenNoCheckoutHasBeenRequested(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $customer = CustomerTestFactory::new()->withEmail('buyer-7@example.com')->linkedToIdentity($identity->id()->toString())->create();
        $this->store($customer);
        $order = OrderTestFactory::new()->withCustomerId($customer->id()->toString())->create();
        $this->store($order);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $order->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertResponseRedirects('/login');
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-4@example.com'));
        $id = $this->placeOrder($client);

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
    }

    #[Test]
    public function itRefusesToCancelAnOrderAlreadyCaptured(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-5@example.com'));
        $id = $this->placeOrder($client);
        $payment = $this->service(OrderPaymentFinderInterface::class)->ofOrder($id);
        self::assertNotNull($payment);
        $this->service(CommandBusInterface::class)->dispatch(new CaptureOrderPayment($payment->id));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-error"]');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(AppOrderStatus::PLACED, $order->status);
    }

    #[Test]
    public function itRefusesToCancelWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-6@example.com'));
        $id = $this->placeOrder($client);

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itRefusesToCancelAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('owner@example.com'));
        $id = $this->placeOrder($client);

        $client->request('GET', '/logout');
        $this->loginAs($client, $this->registerCustomer('intruder@example.com'));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function itRefusesToShowAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('owner@example.com'));
        $id = $this->placeOrder($client);

        $client->request('GET', '/logout');
        $this->loginAs($client, $this->registerCustomer('intruder@example.com'));

        // When
        $client->request('GET', \sprintf('/sales/orders/%s', $id));

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    protected static function browser(): KernelBrowser
    {
        $client = parent::browser();

        self::$lastReturnUrl = null;
        self::getContainer()->set('globex.client', new MockHttpClient(
            static function (string $method, string $url, array $options): MockResponse {
                /** @var array{returnUrl: string} $body */
                $body = json_decode((string) $options['body'], true, 512, \JSON_THROW_ON_ERROR);
                self::$lastReturnUrl = $body['returnUrl'];

                return new MockResponse(
                    json_encode(['chargeReference' => 'GLBX-TEST-REF', 'checkoutUrl' => self::CHECKOUT_URL], \JSON_THROW_ON_ERROR),
                    ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
                );
            },
        ));

        return $client;
    }

    private function registerCustomer(string $email): Identity
    {
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(CustomerTestFactory::new()->withEmail($email)->linkedToIdentity($identity->id()->toString())->create());

        return $identity;
    }

    private function placeOrder(KernelBrowser $client): string
    {
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();

        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id()->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);

        $client->submit($form);

        if (1 !== preg_match('#/sales/orders/([0-9a-f-]{36})$#', (string) self::$lastReturnUrl, $matches)) {
            self::fail(\sprintf('No order id found in the return URL sent to the payment gateway: "%s".', self::$lastReturnUrl));
        }

        return $matches[1];
    }
}
