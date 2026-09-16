<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

final class TotpControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheEnrollmentForm(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $this->path('storefront_totp_enroll'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="totp-provisioning-uri"]')->count());
    }

    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);
        $crawler = $client->request('GET', $this->path('storefront_totp_enroll'));
        $secret = $this->secretFromProvisioningUri($crawler->filter('[data-testid="totp-provisioning-uri"]')->text());
        $form = $crawler->filter('[data-testid="totp-confirm-form"]')->form();

        // When
        $form->setValues(['totp_confirm[code]' => TOTP::createFromSecret($secret, Clock::get())->now()]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_account_show'));
    }

    #[Test]
    public function itRefusesWithWrongCode(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);
        $crawler = $client->request('GET', $this->path('storefront_totp_enroll'));
        $form = $crawler->filter('[data-testid="totp-confirm-form"]')->form();

        // When
        $form->setValues(['totp_confirm[code]' => '000000']);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-error"]')->count());
    }

    #[Test]
    public function itRefusesWhenNotAuthenticated(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_totp_enroll'));

        // Then
        self::assertResponseRedirects($this->path('security_login'));
    }

    /**
     * @return non-empty-string
     */
    private function secretFromProvisioningUri(string $provisioningUri): string
    {
        parse_str((string) parse_url($provisioningUri, \PHP_URL_QUERY), $query);
        Assert::stringNotEmpty($query['secret']);

        return $query['secret'];
    }
}
