<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\View\Report;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\JsonView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonView::class)]
class JsonViewTest extends TestCase
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

    public function testViewCanBeInstantiated(): void
    {
        $view = new JsonView();

        $this->assertInstanceOf(JsonView::class, $view);
    }

    public function testViewExtendsBaseJsonView(): void
    {
        $view = new JsonView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\JsonView::class, $view);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(JsonView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflection = new \ReflectionMethod(JsonView::class, 'display');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(JsonView::class, 'display');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(JsonView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflection->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflection = new \ReflectionClass(JsonView::class);

        $this->assertFalse($reflection->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(JsonView::class);

        $this->assertFalse($reflection->isFinal());
    }

    public function testViewClassName(): void
    {
        $reflection = new \ReflectionClass(JsonView::class);

        $this->assertSame('JsonView', $reflection->getShortName());
    }

    public function testViewUsesModelForData(): void
    {
        // The display method uses $this->getModel() to get data
        $reflection = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        // Extract the display method body
        $this->assertStringContainsString('getModel', $source);
    }

    public function testViewUsesRunChecksFromModel(): void
    {
        $reflection = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('runChecks', $source);
    }

    public function testViewUsesToJsonFromModel(): void
    {
        $reflection = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('toJson', $source);
    }
}
