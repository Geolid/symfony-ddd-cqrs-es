<?php

declare(strict_types=1);

namespace Storefront\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeSuite;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Playwright\Browser\BrowserInterface;
use Playwright\Page\PageInterface;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Filesystem\Filesystem;

use function Playwright\Testing\expect;

final class SignInContext implements Context
{
    private const string BASE_URL = 'http://nginx:8100';

    private static ?PlaywrightClient $playwright = null;
    private static ?BrowserInterface $browser = null;

    private ?PageInterface $page = null;
    private string $email = '';
    private ?string $password = null;
    private ?string $totpSecret = null;

    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly PasswordStrengthSpecificationInterface $passwordStrength,
        private readonly TotpCipherInterface $totpCipher,
    ) {
    }

    #[BeforeSuite]
    public static function connectBrowser(): void
    {
        self::$playwright = PlaywrightFactory::create();
        $builder = self::$playwright->chromium()->withHeadless(true);

        // Locally, `castor qa:e2e` runs Chromium in the sidecar `playwright` container (see
        // compose.yaml) since the app container is Alpine/musl and can't run Playwright's own
        // browser binaries. In CI (bare, glibc), no sidecar is started, so it launches directly.
        $wsEndpoint = getenv('PLAYWRIGHT_WS_ENDPOINT');
        self::$browser = (false !== $wsEndpoint && '' !== $wsEndpoint)
            ? $builder->connect($wsEndpoint)
            : $builder->launch();
    }

    #[AfterSuite]
    public static function disconnectBrowser(): void
    {
        self::$browser?->close();
        self::$browser = null;
        self::$playwright = null;
    }

    #[BeforeScenario]
    public function startPage(): void
    {
        \assert(null !== self::$browser);
        $browserContext = self::$browser->newContext();
        $this->page = $browserContext->newPage();

        if ($this->tracingEnabled()) {
            $browserContext->startTracing($this->page, ['screenshots' => true, 'snapshots' => true]);
        }
    }

    #[AfterScenario]
    public function stopPage(): void
    {
        if (null === $this->page) {
            return;
        }

        $browserContext = $this->page->context();

        if ($this->tracingEnabled()) {
            $browserContext->stopTracing($this->page, \sprintf('%s/trace-%s.zip', $this->traceDir(), uniqid()));
        }

        $browserContext->close();
        $this->page = null;
    }

    #[Given('I have a confirmed account with a password')]
    public function iHaveAConfirmedAccountWithAPassword(): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);

        $this->store($identity, $passwordBuilder->create());

        $this->email = $identityBuilder['email']->value;
        $this->password = $passwordBuilder['password']->value;
    }

    #[Given('I have a confirmed account with a password and TOTP enrolled')]
    public function iHaveAConfirmedAccountWithAPasswordAndTotpEnrolled(): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->totpCipher);

        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create());

        $this->email = $identityBuilder['email']->value;
        $this->password = $passwordBuilder['password']->value;
        $this->totpSecret = $totpBuilder['secret'];
    }

    #[When('I sign in with my email and password')]
    public function iSignInWithMyEmailAndPassword(): void
    {
        \assert(null !== $this->password);
        $page = $this->page();

        $page->goto(self::BASE_URL.'/signin');
        $page->locator('#identify_email')->fill($this->email);
        $page->locator('[data-testid="identify-form"] button[type="submit"]')->click();

        $page->locator('#password')->fill($this->password);
        $page->locator('[data-testid="verify-form"] button[type="submit"]')->click();
    }

    #[When('I enter my current TOTP code')]
    public function iEnterMyCurrentTotpCode(): void
    {
        \assert(null !== $this->totpSecret && '' !== $this->totpSecret);
        $page = $this->page();

        $page->locator('#two-factor-code')->fill(TOTP::createFromSecret($this->totpSecret, Clock::get())->now());
        $page->locator('[data-testid="two-factor-form"] button[type="submit"]')->click();
    }

    #[Then('I should be signed in')]
    public function iShouldBeSignedIn(): void
    {
        expect($this->page())->toHaveURL(self::BASE_URL.'/home');
        expect($this->page()->locator('[data-testid="home-title"]'))->toBeVisible();
    }

    #[Then('I should see the two-factor challenge')]
    public function iShouldSeeTheTwoFactorChallenge(): void
    {
        expect($this->page()->locator('[data-testid="two-factor-form"]'))->toBeVisible();
    }

    private function page(): PageInterface
    {
        \assert(null !== $this->page);

        return $this->page;
    }

    private function tracingEnabled(): bool
    {
        return '1' === getenv('E2E_TRACE');
    }

    private function traceDir(): string
    {
        $dir = getenv('E2E_TRACE_DIR') ?: '/srv/var/e2e/traces';
        new Filesystem()->mkdir($dir);

        return $dir;
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->passwordHasher)
            ->withPasswordStrength($this->passwordStrength);
    }

    private function store(AggregateRoot ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            $this->repositoryManager->get($aggregate::class)->save($aggregate);
        }
    }
}
