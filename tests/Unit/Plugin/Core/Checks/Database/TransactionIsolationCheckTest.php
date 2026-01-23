<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\TransactionIsolationCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionIsolationCheck::class)]
class TransactionIsolationCheckTest extends TestCase
{
    private TransactionIsolationCheck $check;

    protected function setUp(): void
    {
        $this->check = new TransactionIsolationCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.transaction_isolation', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
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

    public function testRunReturnsGoodWhenRepeatableRead(): void
    {
        $database = MockDatabaseFactory::createWithResult('REPEATABLE-READ');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testRunReturnsGoodWhenReadCommitted(): void
    {
        $database = MockDatabaseFactory::createWithResult('READ-COMMITTED');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenReadUncommitted(): void
    {
        $database = MockDatabaseFactory::createWithResult('READ-UNCOMMITTED');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('dirty reads', $result->description);
    }

    public function testRunReturnsWarningWhenSerializable(): void
    {
        $database = MockDatabaseFactory::createWithResult('SERIALIZABLE');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('performance', $result->description);
    }

    public function testRunReturnsWarningWhenIsolationLevelNull(): void
    {
        // When isolation level query returns null
        $database = MockDatabaseFactory::createWithResult(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Unable to determine', $result->description);
    }

    public function testRunNormalizesUnderscoresToHyphens(): void
    {
        // Some MySQL versions return underscores instead of hyphens
        $database = MockDatabaseFactory::createWithResult('REPEATABLE_READ');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testRunNormalizesLowerCaseIsolationLevel(): void
    {
        // Handle lowercase isolation level values
        $database = MockDatabaseFactory::createWithResult('repeatable-read');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testRunHandlesReadUncommittedWithUnderscores(): void
    {
        // READ_UNCOMMITTED with underscores
        $database = MockDatabaseFactory::createWithResult('READ_UNCOMMITTED');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('dirty reads', $result->description);
    }

    public function testRunHandlesReadCommittedWithUnderscores(): void
    {
        // READ_COMMITTED with underscores
        $database = MockDatabaseFactory::createWithResult('READ_COMMITTED');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenExceptionThrown(): void
    {
        // When database throws an exception on query
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Connection lost'));
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Unable to check', $result->description);
        $this->assertStringContainsString('Connection lost', $result->description);
    }

    public function testRunFallsBackToTxIsolationVariable(): void
    {
        // Simulates MySQL 8.0+ throwing exception on @@transaction_isolation
        // then falling back to @@tx_isolation which returns valid value
        // The sequential query mock will throw on first call, return on second
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'exception' => new \RuntimeException('Unknown variable'),
            ],
            [
                'method' => 'loadResult',
                'return' => 'REPEATABLE-READ',
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testRunHandlesMixedCaseSerializable(): void
    {
        // Handle mixed case SERIALIZABLE
        $database = MockDatabaseFactory::createWithResult('Serializable');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('performance', $result->description);
    }
}
