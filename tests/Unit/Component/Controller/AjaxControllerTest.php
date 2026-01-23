<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Controller;

use HealthChecker\Tests\Utilities\MockHealthCheckerComponent;
use HealthChecker\Tests\Utilities\MockHealthCheckRunner;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Controller\AjaxController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AjaxController::class)]
class AjaxControllerTest extends TestCase
{
    private CMSApplication $app;

    private AjaxController $controller;

    protected function setUp(): void
    {
        // Reset static state
        Session::setTokenValid(true);
        Factory::setApplication(null);

        // Create mock application
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);

        $this->controller = new AjaxController();
    }

    protected function tearDown(): void
    {
        // Reset static state after each test
        Session::setTokenValid(true);
        Factory::setApplication(null);
    }

    /**
     * Helper to set up an authorized user for testing
     */
    private function setUpAuthorizedUser(): User
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        return $user;
    }

    /**
     * Helper to create a mock component with a mock runner
     *
     * @param MockHealthCheckRunner $runner The configured mock runner
     */
    private function setUpMockComponent(MockHealthCheckRunner $runner): void
    {
        $component = new MockHealthCheckerComponent();
        $component->setHealthCheckRunner($runner);
        $this->app->setComponent('com_healthchecker', $component);
    }

    public function testAjaxControllerExtendsBaseController(): void
    {
        $this->assertInstanceOf(\Joomla\CMS\MVC\Controller\BaseController::class, $this->controller);
    }

    // =========================================================================
    // Token validation tests
    // =========================================================================

    public function testMetadataRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type'] ?? '');
    }

    public function testCategoryRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Authorization tests - null user
    // =========================================================================

    public function testMetadataRejectsNullUser(): void
    {
        // No user set (null identity)
        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryRejectsNullUser(): void
    {
        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsNullUser(): void
    {
        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsNullUser(): void
    {
        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsNullUser(): void
    {
        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsNullUser(): void
    {
        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Authorization tests - unauthorized user
    // =========================================================================

    public function testMetadataRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        // User has no authorisation for core.manage
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Missing parameter tests
    // =========================================================================

    public function testCategoryRejectsMissingCategoryParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        // Empty input - no category parameter
        $this->app->setInput(new Input([]));

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsMissingSlugParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        // Empty input - no slug parameter
        $this->app->setInput(new Input([]));

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // JSON response header tests
    // =========================================================================

    public function testMetadataSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCategorySetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCheckSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testStatsSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testClearCacheSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testRunSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // Application close tests
    // =========================================================================

    public function testMetadataClosesApplication(): void
    {
        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryClosesApplication(): void
    {
        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckClosesApplication(): void
    {
        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsClosesApplication(): void
    {
        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheClosesApplication(): void
    {
        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunClosesApplication(): void
    {
        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Empty category/slug validation tests
    // =========================================================================

    public function testCategoryRejectsEmptyString(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        $this->app->setInput(new Input([
            'category' => '',
        ]));

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsEmptySlug(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        $this->app->setInput(new Input([
            'slug' => '',
        ]));

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - metadata()
    // =========================================================================

    public function testMetadataReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $runner->setMetadata([
            'categories' => [[
                'slug' => 'system',
                'label' => 'System',
            ]],
            'providers' => [[
                'slug' => 'core',
                'name' => 'Core',
            ]],
            'checks' => [[
                'slug' => 'core.php_version',
                'category' => 'system',
                'title' => 'PHP Version',
            ]],
        ]);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testMetadataHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('getMetadata', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - category()
    // =========================================================================

    public function testCategoryReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'category' => 'system',
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->setCategoryResults([
            'core.php_version' => [
                'status' => 'good',
                'title' => 'PHP Version',
                'description' => 'PHP version is good',
                'slug' => 'core.php_version',
                'category' => 'system',
                'provider' => 'core',
            ],
        ]);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCategoryHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'category' => 'system',
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('runCategory', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCategoryRejectsZeroAsCategory(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'category' => '0',
        ]));

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - check()
    // =========================================================================

    public function testCheckReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'slug' => 'core.php_version',
        ]));

        $result = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'PHP Version',
            description: 'PHP version is good',
            slug: 'core.php_version',
            category: 'system',
            provider: 'core',
        );

        $runner = new MockHealthCheckRunner();
        $runner->setSingleCheckResult($result);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCheckReturnsErrorForNonExistentSlug(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'slug' => 'nonexistent.check',
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->setSingleCheckResult(null);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCheckHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'slug' => 'core.php_version',
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('runSingleCheck', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCheckRejectsZeroAsSlug(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'slug' => '0',
        ]));

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - stats()
    // =========================================================================

    public function testStatsReturnsSuccessfulResponseWithoutCache(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'cache' => 0,
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->setCounts(1, 2, 10);
        $runner->setLastRun(new \DateTimeImmutable('2026-01-23T10:00:00+00:00'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testStatsReturnsSuccessfulResponseWithCache(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'cache' => 1,
            'cache_ttl' => 900,
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->setStatsWithCache([
            'critical' => 0,
            'warning' => 3,
            'good' => 15,
            'total' => 18,
            'lastRun' => '2026-01-23T10:00:00+00:00',
        ]);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testStatsHandlesZeroCacheTtl(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([
            'cache' => 1,
            'cache_ttl' => 0,
        ]));

        $runner = new MockHealthCheckRunner();
        $runner->setCounts(0, 0, 5);
        $runner->setLastRun(null);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testStatsHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->app->setInput(new Input([]));

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('run', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - clearCache()
    // =========================================================================

    public function testClearCacheReturnsSuccessfulResponse(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testClearCacheHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('clearCache', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - run()
    // =========================================================================

    public function testRunReturnsSuccessfulResponse(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $runner->setToArrayResult([
            'lastRun' => '2026-01-23T10:00:00+00:00',
            'summary' => [
                'critical' => 0,
                'warning' => 2,
                'good' => 10,
                'total' => 12,
            ],
            'categories' => [],
            'providers' => [],
            'results' => [],
        ]);
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testRunHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $runner = new MockHealthCheckRunner();
        $runner->throwExceptionOn('run', new \RuntimeException('Test error'));
        $this->setUpMockComponent($runner);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }
}
