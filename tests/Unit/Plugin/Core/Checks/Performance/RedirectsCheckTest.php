<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\RedirectsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RedirectsCheck::class)]
class RedirectsCheckTest extends TestCase
{
    private RedirectsCheck $check;

    protected function setUp(): void
    {
        $this->check = new RedirectsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.redirects', $this->check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenRedirectComponentDisabled(): void
    {
        // Mock returns 0 for loadResult, meaning com_redirect is not enabled
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not enabled', $result->description);
    }

    public function testRunReturnsGoodWhenTableNotFound(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueriesAndTableList(
            [
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // com_redirect enabled
            ],
            [], // No tables exist (empty table list)
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('table not found', $result->description);
    }

    public function testRunReturnsGoodWhenNoIssuesFound(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueriesAndTableList(
            [
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // com_redirect enabled
                [
                    'method' => 'loadColumn',
                    'return' => [],
                ], // No redirect chains
                [
                    'method' => 'loadResult',
                    'return' => 0,
                ], // No redirect loops
                [
                    'method' => 'loadResult',
                    'return' => 5,
                ], // 5 total redirects
            ],
            ['#__redirect_links'], // Table exists
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No redirect chains or loops detected', $result->description);
        $this->assertStringContainsString('5 active redirect', $result->description);
    }

    public function testRunReturnsWarningWhenChainsFound(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueriesAndTableList(
            [
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // com_redirect enabled
                [
                    'method' => 'loadColumn',
                    'return' => ['/old-page', '/another-old-page'],
                ], // 2 redirect chains
                [
                    'method' => 'loadResult',
                    'return' => 0,
                ], // No loops
            ],
            ['#__redirect_links'],
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2 redirect chain(s)', $result->description);
        $this->assertStringContainsString('slow down page loads', $result->description);
    }

    public function testRunReturnsCriticalWhenLoopsFound(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueriesAndTableList(
            [
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // com_redirect enabled
                [
                    'method' => 'loadColumn',
                    'return' => [],
                ], // No chains
                [
                    'method' => 'loadResult',
                    'return' => 3,
                ], // 3 redirect loops!
            ],
            ['#__redirect_links'],
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('3 redirect loop(s)', $result->description);
        $this->assertStringContainsString('infinite redirects', $result->description);
    }

    public function testRunLoopsAreCheckedBeforeChains(): void
    {
        // Both loops and chains exist - loops should take priority (critical > warning)
        $database = MockDatabaseFactory::createWithSequentialQueriesAndTableList(
            [
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // com_redirect enabled
                [
                    'method' => 'loadColumn',
                    'return' => ['/chained'],
                ], // 1 chain
                [
                    'method' => 'loadResult',
                    'return' => 1,
                ], // 1 loop
            ],
            ['#__redirect_links'],
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Loops are critical, chains are warning - critical should win
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }
}
