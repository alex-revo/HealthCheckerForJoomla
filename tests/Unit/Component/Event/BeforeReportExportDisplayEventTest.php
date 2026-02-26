<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportExportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeReportExportDisplayEvent::class)]
class BeforeReportExportDisplayEventTest extends TestCase
{
    public function testEventHasCorrectName(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $this->assertSame(
            HealthCheckerEvents::BEFORE_REPORT_EXPORT_DISPLAY->value,
            $beforeReportExportDisplayEvent->getName(),
        );
    }

    public function testGetHtmlContentReturnsEmptyStringByDefault(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $this->assertSame('', $beforeReportExportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentAddsContent(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $beforeReportExportDisplayEvent->addHtmlContent('<div>Test content</div>');

        $this->assertSame('<div>Test content</div>', $beforeReportExportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentConcatenatesMultipleContents(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $beforeReportExportDisplayEvent->addHtmlContent('<div>First</div>');
        $beforeReportExportDisplayEvent->addHtmlContent('<div>Second</div>');
        $beforeReportExportDisplayEvent->addHtmlContent('<div>Third</div>');

        $expected = "<div>First</div>\n<div>Second</div>\n<div>Third</div>";
        $this->assertSame($expected, $beforeReportExportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentPreservesOrder(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $beforeReportExportDisplayEvent->addHtmlContent('A');
        $beforeReportExportDisplayEvent->addHtmlContent('B');
        $beforeReportExportDisplayEvent->addHtmlContent('C');

        $content = $beforeReportExportDisplayEvent->getHtmlContent();
        $posA = strpos($content, 'A');
        $posB = strpos($content, 'B');
        $posC = strpos($content, 'C');

        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posC, $posB);
    }

    public function testAddHtmlContentAcceptsEmptyString(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $beforeReportExportDisplayEvent->addHtmlContent('');

        $this->assertSame('', $beforeReportExportDisplayEvent->getHtmlContent());
    }

    public function testEventExtendsJoomlaEvent(): void
    {
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $this->assertInstanceOf(\Joomla\Event\Event::class, $beforeReportExportDisplayEvent);
    }
}
