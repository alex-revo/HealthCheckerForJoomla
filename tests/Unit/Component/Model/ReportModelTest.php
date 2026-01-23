<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Model;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Model\ReportModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportModel::class)]
class ReportModelTest extends TestCase
{
    private ?CMSApplication $originalApp = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Store original app if set
        try {
            $this->originalApp = Factory::getApplication();
        } catch (\Exception) {
            $this->originalApp = null;
        }

        // Set up a mock application
        $app = new CMSApplication();
        Factory::setApplication($app);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->originalApp);

        parent::tearDown();
    }

    public function testModelCanBeInstantiated(): void
    {
        $model = new ReportModel();

        $this->assertInstanceOf(ReportModel::class, $model);
    }

    public function testModelExtendsBaseDatabaseModel(): void
    {
        $model = new ReportModel();

        $this->assertInstanceOf(\Joomla\CMS\MVC\Model\BaseDatabaseModel::class, $model);
    }

    public function testRunChecksMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'runChecks'));
    }

    public function testRunChecksMethodReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'runChecks');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testGetRunnerMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getRunner'));
    }

    public function testGetRunnerMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getRunner');

        $this->assertTrue($reflection->isPublic());
    }

    public function testGetResultsMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getResults'));
    }

    public function testGetResultsReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getResults');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testGetResultsByCategoryMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getResultsByCategory'));
    }

    public function testGetResultsByCategoryReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getResultsByCategory');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testGetFilteredResultsMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getFilteredResults'));
    }

    public function testGetFilteredResultsAcceptsNullParameters(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getFilteredResults');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);

        // Both parameters should allow null
        $this->assertTrue($parameters[0]->allowsNull());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function testGetFilteredResultsParameterNames(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getFilteredResults');
        $parameters = $reflection->getParameters();

        $this->assertSame('statusFilter', $parameters[0]->getName());
        $this->assertSame('categoryFilter', $parameters[1]->getName());
    }

    public function testGetCriticalCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getCriticalCount'));
    }

    public function testGetCriticalCountReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getCriticalCount');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetWarningCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getWarningCount'));
    }

    public function testGetWarningCountReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getWarningCount');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetGoodCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getGoodCount'));
    }

    public function testGetGoodCountReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getGoodCount');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetTotalCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getTotalCount'));
    }

    public function testGetTotalCountReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getTotalCount');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetLastRunMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getLastRun'));
    }

    public function testGetLastRunReturnTypeAllowsNull(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'getLastRun');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    public function testToJsonMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'toJson'));
    }

    public function testToJsonReturnType(): void
    {
        $reflection = new \ReflectionMethod(ReportModel::class, 'toJson');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testModelHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(ReportModel::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\Model',
            $reflection->getNamespaceName(),
        );
    }

    public function testModelIsNotAbstract(): void
    {
        $reflection = new \ReflectionClass(ReportModel::class);

        $this->assertFalse($reflection->isAbstract());
    }

    public function testModelIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(ReportModel::class);

        $this->assertFalse($reflection->isFinal());
    }

    public function testHealthCheckRunnerPropertyExists(): void
    {
        $reflection = new \ReflectionClass(ReportModel::class);

        $this->assertTrue($reflection->hasProperty('healthCheckRunner'));
    }

    public function testHealthCheckRunnerPropertyIsPrivate(): void
    {
        $reflection = new \ReflectionProperty(ReportModel::class, 'healthCheckRunner');

        $this->assertTrue($reflection->isPrivate());
    }

    public function testHealthCheckRunnerPropertyAllowsNull(): void
    {
        $reflection = new \ReflectionProperty(ReportModel::class, 'healthCheckRunner');
        $type = $reflection->getType();

        $this->assertNotNull($type);
        $this->assertTrue($type->allowsNull());
    }
}
