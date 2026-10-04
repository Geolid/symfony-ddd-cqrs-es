<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Browser\RegistrationExtensionInterface;
use Storefront\Tests\Browser\StorefrontKernelBrowser;
use Storefront\Tests\Browser\StorefrontPlaywrightBrowser;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * @method StorefrontKernelBrowser     browser()
 * @method StorefrontPlaywrightBrowser playwrightBrowser()
 */
abstract class AbstractStorefrontTestCase extends WebTestCase
{
    use EventSourcingTrait;
    use HasBrowser;
    use InteractsWithMailer;
    use ServiceLocatorTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $_SERVER['KERNEL_BROWSER_CLASS'] = StorefrontKernelBrowser::class;
        $_SERVER['PLAYWRIGHT_BROWSER_CLASS'] = StorefrontPlaywrightBrowser::class;
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? (bool) ($_SERVER['APP_DEBUG'] ?? true),
            'storefront',
        );
    }

    /**
     * Uniform across backends (`StorefrontKernelBrowser`/`StorefrontPlaywrightBrowser` both carry
     * every Extension's own interface) — real, headed Chromium (`BROWSER_HEADED=1`, run outside
     * Docker so the window renders on the host) only for a deliberate, manual run; everyday/CI
     * runs stay on the fast KernelBrowser.
     */
    protected function activeBrowser(): Browser&AuthenticationExtensionInterface&RegistrationExtensionInterface
    {
        if ('1' === getenv('BROWSER_HEADED')) {
            return $this->playwrightBrowser();
        }

        return $this->browser()->disableReboot();
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function path(string $route, array $params = []): string
    {
        return $this->service(UrlGeneratorInterface::class)->generate($route, $params);
    }
}
