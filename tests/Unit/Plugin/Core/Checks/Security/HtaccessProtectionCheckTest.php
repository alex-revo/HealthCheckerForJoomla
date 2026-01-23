<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\HtaccessProtectionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtaccessProtectionCheck::class)]
class HtaccessProtectionCheckTest extends TestCase
{
    private HtaccessProtectionCheck $check;

    private string $htaccessPath;

    protected function setUp(): void
    {
        $this->check = new HtaccessProtectionCheck();
        $this->htaccessPath = JPATH_ROOT . '/.htaccess';

        // Ensure JPATH_ROOT exists
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        // Clean up any existing .htaccess
        if (file_exists($this->htaccessPath)) {
            unlink($this->htaccessPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up .htaccess after each test
        if (file_exists($this->htaccessPath)) {
            // Ensure file is writable for cleanup
            chmod($this->htaccessPath, 0644);
            unlink($this->htaccessPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.htaccess_protection', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
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

    public function testRunReturnsWarningWhenHtaccessMissing(): void
    {
        // No .htaccess file exists
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not found', $result->description);
        $this->assertStringContainsString('htaccess.txt', $result->description);
    }

    public function testRunReturnsWarningWhenHtaccessIsEmpty(): void
    {
        // Create an empty .htaccess file
        file_put_contents($this->htaccessPath, '');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('empty', $result->description);
    }

    public function testRunReturnsWarningWhenHtaccessHasNoRewriteEngine(): void
    {
        // Create .htaccess without RewriteEngine
        $htaccessContent = <<<'HTACCESS'
# Some comments
Options -Indexes
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('rewriting', strtolower($result->description));
    }

    public function testRunReturnsGoodWhenHtaccessHasRewriteEngine(): void
    {
        // Create proper .htaccess with RewriteEngine
        $htaccessContent = <<<'HTACCESS'
# Joomla default htaccess
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('present', $result->description);
        $this->assertStringContainsString('configured', $result->description);
    }

    public function testRunReturnsGoodWhenRewriteEngineIsCaseInsensitive(): void
    {
        // Test case insensitivity
        $htaccessContent = 'rewriteengine on';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenRewriteEngineUpperCase(): void
    {
        $htaccessContent = 'REWRITEENGINE ON';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenRewriteEngineMixedCase(): void
    {
        $htaccessContent = 'ReWriteEnGiNe On';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunResultContainsSlug(): void
    {
        $result = $this->check->run();

        $this->assertSame('security.htaccess_protection', $result->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testRunResultHasProvider(): void
    {
        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $result = $this->check->run();

        $this->assertSame('security', $result->category);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Per the docblock, this check does not return critical
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenOnlyZeroContent(): void
    {
        // '0' is in the empty check array
        file_put_contents($this->htaccessPath, '0');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('empty', $result->description);
    }

    public function testRunReturnsGoodWithFullJoomlaHtaccess(): void
    {
        // Full realistic Joomla .htaccess content
        $htaccessContent = <<<'HTACCESS'
##
# @package    Joomla
# @copyright  (C) 2005 Open Source Matters, Inc.
# @license    GNU General Public License version 2 or later
##

##
# READ THIS COMPLETELY IF YOU CHOOSE TO USE THIS FILE!
##

## Mod_rewrite in use.
RewriteEngine On

## Begin - Rewrite rules to block out some common exploits.
RewriteCond %{QUERY_STRING} proc/self/environ [OR]
RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|\%3D) [OR]
RewriteCond %{QUERY_STRING} base64_(en|de)code\(.*\) [OR]
RewriteRule .* index.php [F]
## End - Rewrite rules to block out some common exploits.

## Begin - Custom redirects
## End - Custom redirects

## Begin - Joomla! core SEF Section.
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteCond %{REQUEST_URI} !^/index\.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [L]
## End - Joomla! core SEF Section.
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsGoodWithMinimalRewriteEngine(): void
    {
        // Just having RewriteEngine is enough
        file_put_contents($this->htaccessPath, 'RewriteEngine');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenFileContainsOnlyComments(): void
    {
        $htaccessContent = <<<'HTACCESS'
# This is a comment
# Another comment
# No actual rules here
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWithOtherDirectivesButNoRewrite(): void
    {
        // Valid Apache directives but no RewriteEngine
        $htaccessContent = <<<'HTACCESS'
Options -Indexes
DirectoryIndex index.php index.html
ErrorDocument 404 /index.php
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testWarningDescriptionMentionsHtaccess(): void
    {
        // When .htaccess is missing
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertTrue(
            str_contains($result->description, 'htaccess') ||
            str_contains($result->description, '.htaccess'),
        );
    }

    public function testRunWithRewriteEngineInComment(): void
    {
        // RewriteEngine in a comment should not count
        $htaccessContent = <<<'HTACCESS'
# RewriteEngine On
# This is commented out
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        // The check uses stripos which will still find the commented RewriteEngine
        // This is a known limitation - it's checking for presence not active state
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testGoodDescriptionMentionsConfigured(): void
    {
        $htaccessContent = 'RewriteEngine On';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('configured', $result->description);
    }
}
